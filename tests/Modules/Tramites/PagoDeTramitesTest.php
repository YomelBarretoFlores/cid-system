<?php

declare(strict_types=1);

use App\Modules\Matricula\Models\Matricula;
use App\Modules\Pagos\Actions\AnularPago;
use App\Modules\Pagos\Actions\RechazarPago;
use App\Modules\Pagos\Actions\ValidarPago;
use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Modules\Pagos\Data\PagoData;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Actions\AplicarPagoValidado;
use App\Modules\Tramites\Actions\ObservarPorPagoRechazado;
use App\Modules\Tramites\Actions\RevertirPagoAnulado;
use App\Modules\Tramites\Actions\SolicitarTramite;
use App\Modules\Tramites\Data\DatosDeSolicitud;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Events\TramiteActualizado;
use App\Modules\Tramites\Models\Movimiento;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create();
    $this->matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id]);
    $this->constancia = Concepto::factory()->create(['nombre' => 'Constancia de estudios', 'tipo' => TipoConcepto::Constancia]);
    $this->cajero = User::factory()->create()->givePermissionTo(['pagos.validar', 'pagos.anular']);
});

function tramiteDeConstancia(Persona $persona, Matricula $matricula): Tramite
{
    return app(SolicitarTramite::class)($persona->id, TipoTramite::query()->where('codigo', 'CONSTANCIA-MATRICULA')->value('id'), new DatosDeSolicitud(matriculaId: $matricula->id));
}

function pagoDeConstancia(Persona $persona, Concepto $concepto, string $estado = 'pendiente'): Pago
{
    return Pago::factory()->{$estado}()->for($concepto)->create(['persona_id' => $persona->id]);
}

it('applies an unused validated payment when the trámite is requested, moving it to the payment stage with a single e-mail', function (): void {
    Event::fake([TramiteActualizado::class]);
    $usado = pagoDeConstancia($this->persona, $this->constancia, 'validado');
    Tramite::factory()->create(['persona_id' => $this->persona->id, 'pago_id' => $usado->id]);
    $libre = pagoDeConstancia($this->persona, $this->constancia, 'validado');

    $tramite = tramiteDeConstancia($this->persona, $this->matricula)->refresh();

    expect($tramite)->estado->toBe(EstadoTramite::EnProceso)->etapa_actual->toBe(1)->pago_id->toBe($libre->id)
        ->and(Movimiento::query()->where('tramite_id', $tramite->id)->pluck('etapa_nombre')->all())->toBe(['Solicitud recibida', 'Pago validado']);
    Event::assertDispatchedTimes(TramiteActualizado::class, 1);
});

it('waits for the fee and moves on when Pagos validates a payment of that concept type for the person', function (): void {
    $tramite = tramiteDeConstancia($this->persona, $this->matricula);
    $otraPersona = pagoDeConstancia(Persona::factory()->create(), $this->constancia);
    $matricula = pagoDeConstancia($this->persona, Concepto::factory()->create(['tipo' => TipoConcepto::Matricula]));
    $propio = pagoDeConstancia($this->persona, $this->constancia);

    app(ValidarPago::class)($otraPersona->id, $this->cajero);
    app(ValidarPago::class)($matricula->id, $this->cajero);
    expect($tramite->refresh()->estado)->toBe(EstadoTramite::PendientePago);

    app(ValidarPago::class)($propio->id, $this->cajero);

    expect($tramite->refresh())->estado->toBe(EstadoTramite::EnProceso)->etapa_actual->toBe(1)->pago_id->toBe($propio->id);
});

it('gives a validated payment to the oldest waiting trámite only, and applying it again changes nothing', function (): void {
    $antiguo = tramiteDeConstancia($this->persona, $this->matricula);
    $reciente = tramiteDeConstancia($this->persona, Matricula::factory()->activa()->create(['persona_id' => $this->persona->id]));
    $pago = pagoDeConstancia($this->persona, $this->constancia, 'validado');

    expect(app(AplicarPagoValidado::class)($pago->id)?->id)->toBe($antiguo->id)
        ->and(app(AplicarPagoValidado::class)($pago->id))->toBeNull()
        ->and($reciente->refresh()->estado)->toBe(EstadoTramite::PendientePago)
        ->and(Tramite::query()->where('pago_id', $pago->id)->count())->toBe(1);
});

it('observes the waiting trámite when its receipt is rejected and clears it when a payment is validated', function (): void {
    $tramite = tramiteDeConstancia($this->persona, $this->matricula);

    app(RechazarPago::class)(pagoDeConstancia($this->persona, $this->constancia)->id, $this->cajero, 'El número de operación no existe.');

    expect($tramite->refresh())->estado->toBe(EstadoTramite::Observado)->observacion->toBe(ObservarPorPagoRechazado::OBSERVACION);

    app(ValidarPago::class)(pagoDeConstancia($this->persona, $this->constancia)->id, $this->cajero);

    expect($tramite->refresh())->estado->toBe(EstadoTramite::EnProceso)->observacion->toBeNull()->etapa_actual->toBe(1);
});

it('returns an unfinished trámite to pending payment when its payment is voided, and leaves a finished one as it is', function (): void {
    $tramite = tramiteDeConstancia($this->persona, $this->matricula);
    $pago = pagoDeConstancia($this->persona, $this->constancia);
    app(ValidarPago::class)($pago->id, $this->cajero);

    app(AnularPago::class)($pago->id, $this->cajero, 'Depósito duplicado por error del banco.');

    expect($tramite->refresh())->estado->toBe(EstadoTramite::PendientePago)->etapa_actual->toBe(0)->pago_id->toBeNull()
        ->and(Movimiento::query()->where('tramite_id', $tramite->id)->latest('id')->value('comentario'))->toBe(RevertirPagoAnulado::COMENTARIO);

    $otro = pagoDeConstancia($this->persona, $this->constancia, 'validado');
    app(AplicarPagoValidado::class)($otro->id);
    DB::table('tra_tramites')->where('id', $tramite->id)->update(['estado' => 'finalizado', 'etapa_actual' => 3, 'finalizado_en' => now()]);

    expect(app(RevertirPagoAnulado::class)($otro->id))->toBeNull()
        ->and($tramite->refresh()->pago_id)->toBe($otro->id);
});

it('uses the oldest of several free payments', function (): void {
    $antiguo = Pago::factory()->validado()->for($this->constancia)->create(['persona_id' => $this->persona->id, 'fecha_pago' => '2026-09-01']);
    Pago::factory()->validado()->for($this->constancia)->create(['persona_id' => $this->persona->id, 'fecha_pago' => '2026-10-01']);

    expect(tramiteDeConstancia($this->persona, $this->matricula)->refresh()->pago_id)->toBe($antiguo->id);
});

it('keeps the request waiting for a payment when the free one can no longer be used', function (): void {
    pagoDeConstancia($this->persona, $this->constancia, 'validado');
    $this->app->bind(VerificacionDePago::class, fn (): VerificacionDePago => new class implements VerificacionDePago
    {
        public function __invoke(int $pagoId, int $personaId, TipoConcepto $servicio): PagoData
        {
            throw new ReglaDeNegocioIncumplida('El pago fue anulado.');
        }
    });
    Event::fake([TramiteActualizado::class]);

    $tramite = tramiteDeConstancia($this->persona, $this->matricula)->refresh();

    expect($tramite)->estado->toBe(EstadoTramite::PendientePago)->pago_id->toBeNull();
    Event::assertDispatchedTimes(TramiteActualizado::class, 1);
});

it('does not clear an observation made by staff when a fee payment is validated', function (): void {
    $tramite = tramiteDeConstancia($this->persona, $this->matricula);
    DB::table('tra_tramites')->where('id', $tramite->id)->update(['estado' => 'observado', 'observacion' => 'Adjunta tu documento de identidad legible.']);

    expect(app(AplicarPagoValidado::class)(pagoDeConstancia($this->persona, $this->constancia, 'validado')->id))->toBeNull()
        ->and($tramite->refresh()->observacion)->toBe('Adjunta tu documento de identidad legible.');
});
