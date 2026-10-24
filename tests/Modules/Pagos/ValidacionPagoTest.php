<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\AnularPago;
use App\Modules\Pagos\Actions\RechazarPago;
use App\Modules\Pagos\Actions\ValidarPago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Events\PagoAnulado;
use App\Modules\Pagos\Events\PagoRechazado;
use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Event;

beforeEach(function (): void {
    Event::fake([PagoValidado::class, PagoRechazado::class, PagoAnulado::class]);
    $this->cajero = User::factory()->create();
    $this->travelTo(now()->startOfSecond());
});

it('allows only the payment lifecycle transitions', function (EstadoPago $origen, EstadoPago $destino, bool $permitida): void {
    expect($origen->puedeTransitarA($destino))->toBe($permitida);
})->with([
    'pending to validated' => [EstadoPago::Pendiente, EstadoPago::Validado, true],
    'pending to rejected' => [EstadoPago::Pendiente, EstadoPago::Rechazado, true],
    'validated to voided' => [EstadoPago::Validado, EstadoPago::Anulado, true],
    'pending to voided' => [EstadoPago::Pendiente, EstadoPago::Anulado, false],
    'validated to pending' => [EstadoPago::Validado, EstadoPago::Pendiente, false],
    'validated to rejected' => [EstadoPago::Validado, EstadoPago::Rechazado, false],
    'rejected to validated' => [EstadoPago::Rechazado, EstadoPago::Validado, false],
    'voided to validated' => [EstadoPago::Anulado, EstadoPago::Validado, false],
]);

it('validates a pending payment recording who validated it and when', function (): void {
    $pago = Pago::factory()->create();

    $resultado = app(ValidarPago::class)($pago->id, $this->cajero);

    expect($pago->refresh())
        ->estado->toBe(EstadoPago::Validado)
        ->validado_por->toBe($this->cajero->id)
        ->validado_en->toEqual(now())
        ->and($resultado->is($pago))->toBeTrue();
    Event::assertDispatchedTimes(PagoValidado::class);
    Event::assertDispatched(PagoValidado::class, fn (PagoValidado $evento): bool => $evento->pagoId === $pago->id);
});

it('treats validating an already validated payment as a harmless retry', function (): void {
    $pago = Pago::factory()->validado()->create();
    $validadorOriginal = $pago->validado_por;

    app(ValidarPago::class)($pago->id, $this->cajero);

    expect($pago->refresh()->validado_por)->toBe($validadorOriginal);
    Event::assertNotDispatched(PagoValidado::class);
});

it('refuses to validate a rejected or voided payment', function (string $estado, string $mensaje): void {
    $pago = Pago::factory()->{$estado}()->create();

    expect(fn () => app(ValidarPago::class)($pago->id, $this->cajero))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
    expect($pago->refresh()->estado->value)->toBe($estado);
    Event::assertNotDispatched(PagoValidado::class);
})->with([
    'rejected' => ['rechazado', 'Un pago Rechazado no puede pasar a Validado.'],
    'voided' => ['anulado', 'Un pago Anulado no puede pasar a Validado.'],
]);

it('rejects a pending payment with the reviewer and the reason', function (): void {
    $pago = Pago::factory()->create();

    app(RechazarPago::class)($pago->id, $this->cajero, '  El recibo no es legible.  ');

    expect($pago->refresh())
        ->estado->toBe(EstadoPago::Rechazado)
        ->validado_por->toBe($this->cajero->id)
        ->validado_en->toEqual(now())
        ->motivo_rechazo->toBe('El recibo no es legible.');
    Event::assertDispatched(PagoRechazado::class, fn (PagoRechazado $evento): bool => $evento->pagoId === $pago->id);
});

it('requires a reason to reject or void a payment', function (string $action, string $estado, string $mensaje): void {
    $pago = Pago::factory()->{$estado}()->create();

    expect(fn () => app($action)($pago->id, $this->cajero, '   '))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
    expect($pago->refresh()->estado->value)->toBe($estado);
})->with([
    'reject' => [RechazarPago::class, 'pendiente', 'Indique el motivo del rechazo.'],
    'void' => [AnularPago::class, 'validado', 'Indique el motivo de la anulación.'],
]);

it('refuses to reject a validated payment', function (): void {
    $pago = Pago::factory()->validado()->create();

    expect(fn () => app(RechazarPago::class)($pago->id, $this->cajero, 'Monto incorrecto.'))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Un pago Validado no puede pasar a Rechazado.');
    Event::assertNotDispatched(PagoRechazado::class);
});

it('voids a validated payment keeping who validated it', function (): void {
    $pago = Pago::factory()->validado()->create();
    $validadorOriginal = $pago->validado_por;

    app(AnularPago::class)($pago->id, $this->cajero, 'Pago registrado dos veces.');

    expect($pago->refresh())
        ->estado->toBe(EstadoPago::Anulado)
        ->validado_por->toBe($validadorOriginal)
        ->anulado_por->toBe($this->cajero->id)
        ->anulado_en->toEqual(now())
        ->motivo_anulacion->toBe('Pago registrado dos veces.');
    Event::assertDispatched(PagoAnulado::class, fn (PagoAnulado $evento): bool => $evento->pagoId === $pago->id);
});

it('refuses to void a payment that was never validated', function (): void {
    $pago = Pago::factory()->create();

    expect(fn () => app(AnularPago::class)($pago->id, $this->cajero, 'Error de caja.'))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'Un pago Pendiente no puede pasar a Anulado.');
    Event::assertNotDispatched(PagoAnulado::class);
});

it('treats rejecting or voiding twice as a harmless retry', function (string $action, string $estado, string $evento): void {
    $pago = Pago::factory()->{$estado}()->create();
    $antes = $pago->refresh()->getAttributes();

    app($action)($pago->id, $this->cajero, 'Otro motivo.');

    expect($pago->refresh()->getAttributes())->toBe($antes);
    Event::assertNotDispatched($evento);
})->with([
    'reject' => [RechazarPago::class, 'rechazado', PagoRechazado::class],
    'void' => [AnularPago::class, 'anulado', PagoAnulado::class],
]);
