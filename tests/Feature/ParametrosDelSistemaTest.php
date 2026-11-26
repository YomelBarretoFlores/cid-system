<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Actions\SolicitarExamenDeClasificacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use App\Shared\Parametros\Actions\GuardarParametrosDelSistema;
use App\Shared\Parametros\Contracts\ParametrosDelSistema;
use App\Shared\Parametros\Filament\EditarParametrosDelSistema;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-11-16 10:00', 'America/Lima'));
    $this->administrador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoUsuario::Gestionar->value]);
});

it('starts with the values the system applied before they became editable', function (): void {
    expect(app(ParametrosDelSistema::class)()->comoArreglo())->toBe([
        'dias_vigencia_clasificacion' => 30,
        'puntaje_maximo_clasificacion' => 100,
        'asistencia_inicio_abre_minutos_antes' => 10,
        'asistencia_cierre_abre_minutos_antes_del_fin' => 15,
        'asistencia_cierre_cierra_minutos_despues_del_fin' => 30,
        'asistencia_confirmacion_minutos_despues_del_fin' => 120,
        'maximo_adjuntos_tramite' => 5,
        'adjunto_maximo_mb' => 5,
        'recibo_maximo_mb' => 5,
        'foto_maximo_mb' => 2,
        'foto_lado_minimo_px' => 200,
        'foto_lado_maximo_px' => 3000,
        'umbral_pocas_vacantes' => 5,
    ]);
});

it('saves the parameters from the page, audits the change and applies it to new placement requests', function (): void {
    Livewire::actingAs($this->administrador)->test(EditarParametrosDelSistema::class)
        ->assertSet('data.dias_vigencia_clasificacion', 30)
        ->fillForm(['dias_vigencia_clasificacion' => 45, 'recibo_maximo_mb' => 8, 'umbral_pocas_vacantes' => 3])
        ->call('guardar')
        ->assertHasNoFormErrors()
        ->assertNotified('Parámetros del sistema guardados');

    $persona = Persona::factory()->create();
    $idioma = Idioma::factory()->create();
    $pago = Pago::factory()->validado()->for(Concepto::factory()->create(['tipo' => TipoConcepto::ExamenClasificacion]))->create(['persona_id' => $persona->id]);
    $solicitud = app(SolicitarExamenDeClasificacion::class)($persona->id, $idioma->id, $pago->id);

    $parametros = app(ParametrosDelSistema::class)();
    expect($parametros->diasVigenciaClasificacion)->toBe(45)
        ->and($parametros->reciboMaximoMb)->toBe(8)
        ->and($parametros->umbralPocasVacantes)->toBe(3)
        ->and($parametros->fotoMaximoMb)->toBe(2)
        ->and($solicitud->refresh()->vence_en->toDateString())->toBe('2026-12-31')
        ->and(DB::table('aud_registros')->where(['tabla' => 'sis_parametros', 'operacion' => 'UPDATE'])->count())->toBe(1);
});

it('refuses values out of range with a Spanish message and keeps the saved ones', function (array $cambios, string $mensaje): void {
    expect(fn () => app(GuardarParametrosDelSistema::class)($cambios, $this->administrador))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje)
        ->and(app(ParametrosDelSistema::class)()->diasVigenciaClasificacion)->toBe(30);
})->with([
    'no validity days' => [['dias_vigencia_clasificacion' => 0], 'La vigencia de la solicitud de examen de clasificación (días) debe ser un número entero de 1 a 365.'],
    'a score scale above 100' => [['puntaje_maximo_clasificacion' => 101], 'El puntaje máximo del examen de clasificación debe ser un número entero de 10 a 100.'],
    'a decimal' => [['recibo_maximo_mb' => '2.5'], 'El tamaño máximo del recibo de pago (MB) debe ser un número entero de 1 a 10.'],
    'too many attachments' => [['maximo_adjuntos_tramite' => 11], 'El máximo de adjuntos por trámite debe ser un número entero de 1 a 10.'],
    'confirmation before the closing call ends' => [['asistencia_cierre_cierra_minutos_despues_del_fin' => 90, 'asistencia_confirmacion_minutos_despues_del_fin' => 60], 'El plazo de confirmación del delegado no puede vencer antes de que cierre el llamado de cierre.'],
]);

it('shows the refusal on the page instead of saving', function (): void {
    Livewire::actingAs($this->administrador)->test(EditarParametrosDelSistema::class)
        ->fillForm(['asistencia_cierre_cierra_minutos_despues_del_fin' => 90, 'asistencia_confirmacion_minutos_despues_del_fin' => 60])
        ->call('guardar')
        ->assertNotified('El plazo de confirmación del delegado no puede vencer antes de que cierre el llamado de cierre.');

    expect(app(ParametrosDelSistema::class)()->confirmacionMinutosDespuesDelFin)->toBe(120);
});

it('keeps a single row of parameters with its ranges in the database', function (): void {
    expect(fn () => DB::table('sis_parametros')->insert(['id' => 2]))->toThrow(QueryException::class)
        ->and(fn () => DB::table('sis_parametros')->update(['recibo_maximo_mb' => 50]))->toThrow(QueryException::class);
});

it('lets only system administrators open and save the parameters', function (): void {
    $ajeno = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, 'pagos.configurar']);

    $this->actingAs($ajeno)->get(EditarParametrosDelSistema::getUrl())->assertForbidden();
    expect(fn () => app(GuardarParametrosDelSistema::class)(['dias_vigencia_clasificacion' => 45], $ajeno))->toThrow(AuthorizationException::class);
    $this->actingAs($this->administrador)->get(EditarParametrosDelSistema::getUrl())->assertOk()->assertSee('Parámetros del sistema')->assertSee('Asistencia docente');
});
