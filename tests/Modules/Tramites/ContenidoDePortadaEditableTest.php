<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Pagos\Models\MedioDePago;
use App\Modules\Tramites\Actions\GuardarTextosDePortada;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Filament\Pages\EditarContenidoDePortada;
use App\Shared\Auth\Enums\PermisoUsuario;
use App\Shared\Auth\Models\User;
use App\Shared\Parametros\Actions\GuardarParametrosDelSistema;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:30', 'America/Lima'));
    $this->encargado = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Configurar->value]);
});

/**
 * @return array<string, mixed>
 */
function contenidoDePortadaEditado(array $cambios = []): array
{
    return [
        'nosotros' => "Primer párrafo sintético.\n\nSegundo párrafo sintético.",
        'pasos_matricula' => [['titulo' => 'Paso sintético', 'detalle' => 'Pague en {lugares_de_pago}.']],
        'pasos_clasificacion' => [['titulo' => 'Clasificación sintética', 'detalle' => 'Detalle de clasificación.']],
        'niveles' => [['nombre' => 'Nivel sintético', 'descripcion' => 'Descripción sintética.', 'logro' => 'Logro sintético.']],
        'preguntas' => [['pregunta' => '¿Pregunta sintética?', 'respuesta' => 'Respuesta sintética.']],
        ...$cambios,
    ];
}

function grupoConDosVacantes(): void
{
    $periodo = Periodo::factory()->create([
        'nombre' => '2026-II', 'estado' => EstadoPeriodo::MatriculaAbierta,
        'matricula_inicio' => '2026-10-06', 'matricula_fin' => '2026-10-31', 'fecha_inicio' => '2026-11-03', 'fecha_fin' => '2026-12-20',
    ]);
    Grupo::factory()->for($periodo)->for(Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->state(['numero' => 1]))
        ->create(['estado' => EstadoGrupo::Abierto, 'modalidad' => 'presencial', 'cupo_maximo' => 20, 'matriculados' => 18]);
}

it('shows today\'s portal texts, the institutional names and the active payment places by default', function (): void {
    $this->get('/')
        ->assertOk()
        ->assertSeeText('Universidad Nacional Santiago Antúnez de Mayolo · Huaraz')
        ->assertSeeInOrder(['<h1', 'Centro de Idiomas y Lenguas Nativas', '</h1>'], false)
        ->assertSeeText('El Centro de Idiomas y Lenguas Nativas de la UNASAM enseña idiomas extranjeros y lenguas originarias del Perú.')
        ->assertSeeText('Pague el derecho de matrícula de su vínculo en Caja presencial, Tesorería UNASAM, Págalo.pe, Banco de la Nación o Transferencia bancaria.')
        ->assertSeeText('Para quien inicia el estudio del idioma: vocabulario, estructuras y expresiones de uso cotidiano.')
        ->assertSeeText('Soy docente del CILN, ¿dónde ingreso?')
        ->assertDontSeeText('{lugares_de_pago}')
        ->assertDontSeeText('Otro.');
});

it('publishes the edited content at once, even when the page was already cached', function (): void {
    $this->get('/')->assertSeeText('Regístrese con su documento de identidad y su correo personal.');

    Livewire::actingAs($this->encargado)->test(EditarContenidoDePortada::class)
        ->assertSet('data.preguntas', fn (array $preguntas): bool => count($preguntas) === 6)
        ->fillForm(contenidoDePortadaEditado())
        ->call('guardar')
        ->assertHasNoFormErrors();

    $this->get('/')
        ->assertSeeInOrder(['<p>Primer párrafo sintético.</p>', '<p>Segundo párrafo sintético.</p>'], false)
        ->assertSeeText('Pague en Caja presencial, Tesorería UNASAM, Págalo.pe, Banco de la Nación o Transferencia bancaria.')
        ->assertSeeText('Clasificación sintética')
        ->assertSeeText('Logro sintético.')
        ->assertSeeText('¿Pregunta sintética?')
        ->assertDontSeeText('Regístrese con su documento de identidad');

    expect(DB::table('aud_registros')->where(['tabla' => 'tra_textos_portada', 'operacion' => 'UPDATE'])->count())->toBe(1);
});

it('lists the payment places from the payment channels catalog', function (): void {
    MedioDePago::query()->whereIn('codigo', ['caja_presencial', 'tesoreria_unasam', 'transferencia'])->update(['activo' => false]);

    $this->get('/')->assertSeeText('Pague el derecho de matrícula de su vínculo en Págalo.pe o Banco de la Nación.');
});

it('highlights few vacancies and states the placement validity from the system parameters', function (): void {
    grupoConDosVacantes();
    $administrador = User::factory()->create()->givePermissionTo(PermisoUsuario::Gestionar->value);
    app(GuardarParametrosDelSistema::class)(['umbral_pocas_vacantes' => 1, 'dias_vigencia_clasificacion' => 45], $administrador);

    $this->get('/')->assertDontSee('text-ambar-texto', false)->assertSeeText('2 vacantes')->assertSeeText('La solicitud queda vigente por 45 días desde su registro en el portal.');
});

it('refreshes the cached page as soon as the system parameters are saved', function (): void {
    grupoConDosVacantes();
    $this->get('/')->assertSee('text-ambar-texto', false)->assertSeeText('La solicitud queda vigente por 30 días desde su registro en el portal.');

    app(GuardarParametrosDelSistema::class)(['umbral_pocas_vacantes' => 1, 'dias_vigencia_clasificacion' => 45], User::factory()->create()->givePermissionTo(PermisoUsuario::Gestionar->value));

    $this->get('/')->assertDontSee('text-ambar-texto', false)->assertSeeText('La solicitud queda vigente por 45 días desde su registro en el portal.');
});

it('keeps the default few-vacancies highlight of five', function (): void {
    grupoConDosVacantes();

    $this->get('/')->assertSee('text-ambar-texto', false);
});

it('escapes markup typed in the portal texts', function (): void {
    app(GuardarTextosDePortada::class)(contenidoDePortadaEditado([
        'preguntas' => [['pregunta' => '<script>alert(1)</script>', 'respuesta' => '<b>negrita</b>']],
    ]), $this->encargado);

    $this->get('/')->assertDontSee('<script>alert(1)</script>', false)->assertSee('&lt;b&gt;negrita&lt;/b&gt;', false);
});

it('refuses empty lists and blank rows', function (array $cambios, string $mensaje): void {
    expect(fn () => app(GuardarTextosDePortada::class)(contenidoDePortadaEditado($cambios), $this->encargado))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
})->with([
    'no FAQ' => [['preguntas' => []], 'Registra de 1 a 12 filas en las preguntas frecuentes.'],
    'blank step' => [['pasos_matricula' => [['titulo' => ' ', 'detalle' => 'x']]], 'Completa cada fila de los pasos de la matrícula, hasta 80 caracteres por campo.'],
    'blank about' => [['nosotros' => ''], 'Completa la presentación del centro, hasta 2000 caracteres.'],
]);

it('lets only staff who configure procedures edit the portal content', function (): void {
    $otro = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTramite::Gestionar->value]);

    expect(fn () => app(GuardarTextosDePortada::class)(contenidoDePortadaEditado(), $otro))->toThrow(AuthorizationException::class);
    $this->actingAs($otro)->get(EditarContenidoDePortada::getUrl())->assertForbidden();
    $this->actingAs($this->encargado)->get(EditarContenidoDePortada::getUrl())->assertOk();
});
