<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Actions\RegistrarResultadoDeClasificacion;
use App\Modules\Matricula\Actions\SolicitarExamenDeClasificacion;
use App\Modules\Matricula\Data\ResultadoDeClasificacion;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Enums\PermisoClasificacion;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ViewMatricula;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ViewSolicitudClasificacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Actions\AnularPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Events\PagoAnulado;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00', 'America/Lima'));
    $this->persona = Persona::factory()->create();
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->cicloVI = Ciclo::factory()->for($this->ingles)->create(['numero' => 6, 'nivel' => NivelCurricular::Intermedio]);
    $this->pagoExamen = Pago::factory()->validado()
        ->for(Concepto::factory()->state(['nombre' => 'Examen de clasificación', 'tipo' => TipoConcepto::ExamenClasificacion]))
        ->create(['persona_id' => $this->persona->id, 'monto' => '50.00', 'numero_operacion' => 'OP-5521']);
    $this->solicitud = app(SolicitarExamenDeClasificacion::class)($this->persona->id, $this->ingles->id, $this->pagoExamen->id);
    $this->coordinador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoClasificacion::valores(), ...PermisoMatricula::valores(), PermisoPago::Ver->value]);
    $this->cajero = User::factory()->create()->givePermissionTo(PermisoPago::Anular->value);
});

function resultadoDeClasificacion(int $cicloId): ResultadoDeClasificacion
{
    return new ResultadoDeClasificacion(CarbonImmutable::parse('2026-10-24'), '78.50', $cicloId, null);
}

it('marks a pending placement request as voided payment when its payment is voided, so it can no longer be evaluated', function (): void {
    app(AnularPago::class)($this->pagoExamen->id, $this->cajero, 'Depósito duplicado en el banco');

    expect($this->solicitud->refresh()->estado)->toBe(EstadoSolicitudClasificacion::PagoAnulado)
        ->and(EstadoSolicitudClasificacion::PagoAnulado->etiqueta())->toBe('Pago anulado')
        ->and(DB::table('aud_registros')->where(['tabla' => 'mat_solicitudes_clasificacion', 'registro_id' => $this->solicitud->id, 'operacion' => 'UPDATE'])->whereRaw("datos_nuevos->>'estado' = 'pago_anulado'")->exists())->toBeTrue()
        ->and(fn () => app(RegistrarResultadoDeClasificacion::class)($this->solicitud->id, resultadoDeClasificacion($this->cicloVI->id), $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La solicitud ya está Pago anulado.')
        ->and(Habilitacion::query()->count())->toBe(0);

    $nuevoPago = Pago::factory()->validado()->for($this->pagoExamen->concepto)->create(['persona_id' => $this->persona->id]);
    $nueva = app(SolicitarExamenDeClasificacion::class)($this->persona->id, $this->ingles->id, $nuevoPago->id);

    expect($nueva->estado)->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion);
});

it('refuses to record a result when the payment was voided before the request caught up', function (): void {
    Event::fake([PagoAnulado::class]);
    app(AnularPago::class)($this->pagoExamen->id, $this->cajero, 'Depósito duplicado en el banco');

    expect(fn () => app(RegistrarResultadoDeClasificacion::class)($this->solicitud->id, resultadoDeClasificacion($this->cicloVI->id), $this->coordinador))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El pago del examen ya no está validado: no se puede registrar el resultado.')
        ->and($this->solicitud->refresh()->estado)->toBe(EstadoSolicitudClasificacion::PendienteEvaluacion)
        ->and(Habilitacion::query()->count())->toBe(0);
});

it('still records a pending result with a validated payment, and keeps it and the enabled cycle when the payment is voided later', function (): void {
    app(RegistrarResultadoDeClasificacion::class)($this->solicitud->id, resultadoDeClasificacion($this->cicloVI->id), $this->coordinador);
    app(AnularPago::class)($this->pagoExamen->id, $this->cajero, 'Depósito duplicado en el banco');

    expect($this->solicitud->refresh())->estado->toBe(EstadoSolicitudClasificacion::Evaluado)->ciclo_asignado_id->toBe($this->cicloVI->id)
        ->and(Habilitacion::query()->sole())->ciclo_id->toBe($this->cicloVI->id)->origen->toBe(OrigenHabilitacion::Clasificacion);
});

it('shows the payment of a placement request with its state and a link, only to staff who can see payments', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $this->solicitud->getRouteKey()])
        ->assertSeeText('Examen de clasificación')
        ->assertSeeText('S/ 50.00')
        ->assertSeeText('OP-5521')
        ->assertSeeText('Validado')
        ->assertSeeHtml(route('filament.admin.resources.pagos.view', $this->pagoExamen->ulid));

    app(AnularPago::class)($this->pagoExamen->id, $this->cajero, 'Depósito duplicado en el banco');

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $this->solicitud->getRouteKey()])
        ->assertSeeText('Anulado')
        ->assertSeeText('Pago anulado');

    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoClasificacion::Ver->value]));

    Livewire::test(ViewSolicitudClasificacion::class, ['record' => $this->solicitud->getRouteKey()])
        ->assertDontSeeText('OP-5521')
        ->assertDontSeeHtml(route('filament.admin.resources.pagos.view', $this->pagoExamen->ulid));
});

it('shows the payment of an enrollment and its voiding, while the enrollment stays active', function (): void {
    $pago = Pago::factory()->validado()
        ->for(Concepto::factory()->state(['nombre' => 'Derecho de matrícula', 'tipo' => TipoConcepto::Matricula]))
        ->create(['persona_id' => $this->persona->id, 'monto' => '120.00', 'numero_operacion' => 'OP-7310']);
    $matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id, 'grupo_id' => Grupo::factory()->create(['matriculados' => 1])->id, 'pago_id' => $pago->id]);
    $this->actingAs($this->coordinador);

    Livewire::test(ViewMatricula::class, ['record' => $matricula->getRouteKey()])
        ->assertSeeText('Derecho de matrícula')
        ->assertSeeText('S/ 120.00')
        ->assertSeeText('OP-7310')
        ->assertSeeText('Validado')
        ->assertSeeHtml(route('filament.admin.resources.pagos.view', $pago->ulid));

    app(AnularPago::class)($pago->id, $this->cajero, 'Depósito duplicado en el banco');

    Livewire::test(ViewMatricula::class, ['record' => $matricula->getRouteKey()])
        ->assertSeeText('Anulado');

    expect($matricula->refresh()->estado)->toBe(EstadoMatricula::Activa);

    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoMatricula::Ver->value]));

    Livewire::test(ViewMatricula::class, ['record' => $matricula->getRouteKey()])
        ->assertDontSeeText('OP-7310');
});

it('shows the student a placement request whose payment was voided', function (): void {
    $user = User::factory()->create();
    $this->persona->update(['user_id' => $user->id]);
    app(AnularPago::class)($this->pagoExamen->id, $this->cajero, 'Depósito duplicado en el banco');

    $this->actingAs($user)->get(route('estudiante.clasificacion'))
        ->assertOk()
        ->assertSeeText('Inglés')
        ->assertSeeText('Pago anulado');
});
