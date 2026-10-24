<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Actions\ExpirarSolicitudesDeClasificacion;
use App\Modules\Matricula\Actions\MatricularEstudiante;
use App\Modules\Matricula\Actions\RegistrarResultadoDeClasificacion;
use App\Modules\Matricula\Actions\SolicitarExamenDeClasificacion;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ListSolicitudesClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ViewSolicitudClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\SolicitudClasificacionResource;
use App\Modules\Matricula\Livewire\ExamenDeClasificacion;
use App\Modules\Matricula\Livewire\Matricularse;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Testing\TestResponse;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00', 'America/Lima'));
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id]);
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->ciclo1 = Ciclo::factory()->for($this->ingles)->create(['numero' => 1]);
    $this->cicloVI = Ciclo::factory()->for($this->ingles)->create(['numero' => 6, 'nivel' => NivelCurricular::Intermedio]);
    $this->conceptoExamen = Concepto::factory()->create(['tipo' => TipoConcepto::ExamenClasificacion]);
    $this->pago = Pago::factory()->validado()->for($this->conceptoExamen)->create(['persona_id' => $this->persona->id]);
    $this->coordinador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoClasificacion::valores()]);
});

function vSnapshot(TestResponse $pagina): string
{
    preg_match('/wire:snapshot="([^"]+)"/', $pagina->getContent(), $c);

    return html_entity_decode($c[1], ENT_QUOTES);
}

function vReplay(string $snapshot, array $updates, string $metodo): TestResponse
{
    return test()->withHeaders(['X-Livewire' => '1'])->postJson(route('default-livewire.update'), ['components' => [[
        'snapshot' => $snapshot, 'updates' => $updates, 'calls' => [['method' => $metodo, 'params' => [], 'metadata' => []]],
    ]]]);
}

function vResultado(string $fecha, ?int $cicloId = null): ResultadoDeClasificacion
{
    return new ResultadoDeClasificacion(CarbonImmutable::parse($fecha), '78.50', $cicloId ?? test()->cicloVI->id, null);
}

it('replaying another student snapshot shows only the replayer own requests', function (): void {
    SolicitudClasificacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $this->ingles->id, 'vence_en' => '2026-11-11']);
    $snapshot = vSnapshot($this->actingAs($this->user)->get(route('estudiante.clasificacion'))->assertOk());
    $otro = User::factory()->create();
    $otraPersona = Persona::factory()->create(['user_id' => $otro->id]);
    SolicitudClasificacion::factory()->create(['persona_id' => $otraPersona->id, 'idioma_id' => $this->ingles->id, 'vence_en' => '2026-11-22']);

    $this->actingAs($otro);
    $html = vReplay($snapshot, [], '$refresh')->json('components.0.effects.html');

    expect($html)->toContain('22/11/2026')->not->toContain('11/11/2026');
});

it('an unverified account replaying a snapshot cannot request the exam', function (): void {
    $snapshot = vSnapshot($this->actingAs($this->user)->get(route('estudiante.clasificacion'))->assertOk());
    $sinVerificar = User::factory()->unverified()->create();
    $persona = Persona::factory()->create(['user_id' => $sinVerificar->id]);
    $pago = Pago::factory()->validado()->for($this->conceptoExamen)->create(['persona_id' => $persona->id]);

    $this->actingAs($sinVerificar);
    $respuesta = vReplay($snapshot, ['idioma' => (string) $this->ingles->id, 'pago' => (string) $pago->id], 'solicitar');

    expect($respuesta->status())->not->toBe(200)
        ->and(SolicitudClasificacion::query()->exists())->toBeFalse();
});

it('an account without a person record replaying a snapshot cannot request the exam', function (): void {
    $snapshot = vSnapshot($this->actingAs($this->user)->get(route('estudiante.clasificacion'))->assertOk());

    $this->actingAs(User::factory()->create());
    $respuesta = vReplay($snapshot, ['idioma' => (string) $this->ingles->id, 'pago' => (string) $this->pago->id], 'solicitar');

    expect($respuesta->status())->toBe(403)
        ->and(SolicitudClasificacion::query()->exists())->toBeFalse();
});

it('refuses a tampered enrollment-concept payment without creating a request', function (): void {
    $matricula = Pago::factory()->validado()->for(Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]))->create(['persona_id' => $this->persona->id]);

    Livewire::actingAs($this->user)->test(ExamenDeClasificacion::class)
        ->set('idioma', (string) $this->ingles->id)->set('pago', (string) $matricula->id)
        ->call('solicitar')->assertHasErrors(['pago' => 'in']);

    expect(SolicitudClasificacion::query()->exists())->toBeFalse();
});

it('a reader cannot record a result by mounting the hidden action directly', function (): void {
    $solicitud = SolicitudClasificacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $this->ingles->id]);
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoClasificacion::Ver->value]);
    $this->actingAs($lector);

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $solicitud->getRouteKey()])
        ->call('mountAction', 'registrarResultado')
        ->set('mountedActions.0.data', ['fecha_examen' => '2026-10-24', 'puntaje' => '80', 'ciclo_id' => $this->cicloVI->id])
        ->call('callMountedAction');
    Livewire::test(ListSolicitudesClasificacion::class)
        ->call('mountAction', 'registrarResultado', [], ['table' => true, 'recordKey' => $solicitud->getKey()])
        ->call('callMountedAction');

    expect($solicitud->refresh()->estado)->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion)
        ->and(Habilitacion::query()->exists())->toBeFalse();
});

it('the same raw mount sequence records the result for an evaluator', function (): void {
    $solicitud = SolicitudClasificacion::factory()->create(['persona_id' => $this->persona->id, 'idioma_id' => $this->ingles->id]);
    $this->actingAs($this->coordinador);

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $solicitud->getRouteKey()])
        ->call('mountAction', 'registrarResultado')
        ->set('mountedActions.0.data', ['fecha_examen' => '2026-10-24', 'puntaje' => '80', 'ciclo_id' => $this->cicloVI->id])
        ->call('callMountedAction');

    expect($solicitud->refresh()->estado)->toBe(EstadoSolicitudClasificacion::Evaluado);
});

it('a user without the consult permission gets 403 on the list and the view page', function (): void {
    $solicitud = SolicitudClasificacion::factory()->create();
    $cajero = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($cajero)->get(SolicitudClasificacionResource::getUrl('index'))->assertForbidden();
    $this->actingAs($cajero)->get(SolicitudClasificacionResource::getUrl('view', ['record' => $solicitud]))->assertForbidden();
});

it('a recorded result makes enrollment offer and accept the assigned cycle only', function (): void {
    $periodo = Periodo::factory()->conMatriculaAbierta()->create();
    $grupo1 = Grupo::factory()->for($periodo)->for($this->ciclo1)->create(['codigo' => 'ING-I-A']);
    $grupoVI = Grupo::factory()->for($periodo)->for($this->cicloVI)->create(['codigo' => 'ING-VI-A']);
    $pagoMatricula = Pago::factory()->validado()->for(Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]))->create(['persona_id' => $this->persona->id]);
    $solicitud = app(SolicitarExamenDeClasificacion::class)($this->persona->id, $this->ingles->id, $this->pago->id);

    app(RegistrarResultadoDeClasificacion::class)($solicitud->id, vResultado('2026-10-24'), $this->coordinador);

    Livewire::actingAs($this->user)->test(Matricularse::class)->assertSee('ING-VI-A')->assertDontSee('ING-I-A');
    expect(fn () => app(MatricularEstudiante::class)($this->persona->id, $grupo1->id, $pagoMatricula->id))->toThrow(ReglaDeNegocioIncumplida::class);
    app(MatricularEstudiante::class)($this->persona->id, $grupoVI->id, $pagoMatricula->id);
    expect(Matricula::query()->sole()->grupo_id)->toBe($grupoVI->id);
});

it('a request made at 23:30 Lima counts as that Lima day for expiry and exam date', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 23:30', 'America/Lima'));
    $solicitud = app(SolicitarExamenDeClasificacion::class)($this->persona->id, $this->ingles->id, $this->pago->id);
    expect($solicitud->refresh()->vence_en->toDateString())->toBe('2026-11-23');

    expect(fn () => app(RegistrarResultadoDeClasificacion::class)($solicitud->id, vResultado('2026-10-25'), $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La fecha del examen debe estar entre el 24/10/2026 y el 24/10/2026.');

    $this->travelTo(CarbonImmutable::parse('2026-10-25 09:00', 'America/Lima'));
    expect(fn () => app(RegistrarResultadoDeClasificacion::class)($solicitud->id, vResultado('2026-10-23'), $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La fecha del examen debe estar entre el 24/10/2026 y el 25/10/2026.');
    app(RegistrarResultadoDeClasificacion::class)($solicitud->id, vResultado('2026-10-24'), $this->coordinador);
    expect($solicitud->refresh()->fecha_examen->toDateString())->toBe('2026-10-24');
});

it('the panel accepts today in Lima at 23:30 when UTC is already tomorrow', function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 23:30', 'America/Lima'));
    $solicitud = app(SolicitarExamenDeClasificacion::class)($this->persona->id, $this->ingles->id, $this->pago->id);
    $this->actingAs($this->coordinador);

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $solicitud->getRouteKey()])
        ->callAction('registrarResultado', data: ['fecha_examen' => '2026-10-24', 'puntaje' => '80', 'ciclo_id' => $this->cicloVI->id])
        ->assertHasNoFormErrors()->assertNotified('Resultado registrado');

    expect($solicitud->refresh()->estado)->toBe(EstadoSolicitudClasificacion::Evaluado);
});

it('expiry uses the Lima calendar day', function (): void {
    $ayer = SolicitudClasificacion::factory()->create(['vence_en' => '2026-10-23']);
    $hoy = SolicitudClasificacion::factory()->create(['vence_en' => '2026-10-24']);

    $this->travelTo(CarbonImmutable::parse('2026-10-23 23:30', 'America/Lima'));
    expect(app(ExpirarSolicitudesDeClasificacion::class)())->toBe(0);

    $this->travelTo(CarbonImmutable::parse('2026-10-24 00:10', 'America/Lima'));
    expect(app(ExpirarSolicitudesDeClasificacion::class)())->toBe(1)
        ->and($ayer->refresh()->estado)->toBe(EstadoSolicitudClasificacion::Expirado)
        ->and($hoy->refresh()->estado)->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion);
});

it('audits the result update and the enablement insert and replacement', function (): void {
    $solicitud = app(SolicitarExamenDeClasificacion::class)($this->persona->id, $this->ingles->id, $this->pago->id);
    app(RegistrarResultadoDeClasificacion::class)($solicitud->id, vResultado('2026-10-24'), $this->coordinador);
    $otroPago = Pago::factory()->validado()->for($this->conceptoExamen)->create(['persona_id' => $this->persona->id]);
    $segunda = app(SolicitarExamenDeClasificacion::class)($this->persona->id, $this->ingles->id, $otroPago->id);
    app(RegistrarResultadoDeClasificacion::class)($segunda->id, vResultado('2026-10-24', $this->ciclo1->id), $this->coordinador);

    $ops = fn (string $tabla, int $id): array => DB::table('aud_registros')->where(['tabla' => $tabla, 'registro_id' => $id])->orderBy('id')->pluck('operacion')->all();
    $habilitacion = Habilitacion::query()->sole();

    expect($ops('mat_solicitudes_clasificacion', $solicitud->id))->toBe(['INSERT', 'UPDATE'])
        ->and($ops('mat_habilitaciones', $habilitacion->id))->toBe(['INSERT', 'UPDATE'])
        ->and($habilitacion->ciclo_id)->toBe($this->ciclo1->id);
});
