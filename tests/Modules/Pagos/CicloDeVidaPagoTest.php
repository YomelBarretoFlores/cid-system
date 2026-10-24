<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Auth\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function actualizarPago(Pago $pago, array $cambios): int
{
    return DB::table('pag_pagos')->where('id', $pago->id)->update($cambios);
}

it('rejects forbidden state transitions even outside the application', function (string $estado, array $cambios): void {
    $pago = Pago::factory()->{$estado}()->create();
    $usuario = User::factory()->create()->id;
    $cambios = array_map(fn (mixed $valor): mixed => $valor === 'usuario' ? $usuario : $valor, $cambios);

    expect(fn (): int => actualizarPago($pago, $cambios))->toThrow(QueryException::class, 'transición no permitida');
})->with([
    'validated to rejected' => ['validado', ['estado' => 'rechazado', 'motivo_rechazo' => 'Monto incorrecto']],
    'validated back to pending' => ['validado', ['estado' => 'pendiente', 'validado_por' => null, 'validado_en' => null]],
    'rejected to validated' => ['rechazado', ['estado' => 'validado', 'motivo_rechazo' => null]],
    'voided to validated' => ['anulado', ['estado' => 'validado', 'anulado_por' => null, 'anulado_en' => null, 'motivo_anulacion' => null]],
    'pending straight to voided' => ['pendiente', [
        'estado' => 'anulado', 'validado_por' => 'usuario', 'validado_en' => '2026-10-24 10:00:00',
        'anulado_por' => 'usuario', 'anulado_en' => '2026-10-24 10:00:00', 'motivo_anulacion' => 'x',
    ]],
]);

it('freezes the data of a payment once it leaves the pending state', function (string $estado, array $cambios): void {
    $pago = Pago::factory()->{$estado}()->create();
    $cambios = array_map(fn (mixed $valor): mixed => $valor instanceof Closure ? $valor() : $valor, $cambios);

    expect(fn (): int => actualizarPago($pago, $cambios))->toThrow(QueryException::class, 'no admite cambios');
})->with([
    'validated amount' => ['validado', ['monto' => '1.00']],
    'validated operation number' => ['validado', ['numero_operacion' => 'OTRO-1']],
    'validated receipt' => ['validado', ['recibo_path' => 'recibos/otro.pdf']],
    'validated validator' => ['validado', ['validado_por' => fn (): int => User::factory()->create()->id]],
    'voiding data on a payment that stays validated' => ['validado', ['motivo_anulacion' => 'Sin anular']],
    'rejected reason' => ['rechazado', ['motivo_rechazo' => 'Otro motivo']],
    'voided reason' => ['anulado', ['motivo_anulacion' => 'Otro motivo']],
]);

it('still lets a pending payment be corrected and a validated one be voided', function (): void {
    $pendiente = Pago::factory()->create();
    $validado = Pago::factory()->validado()->create();

    actualizarPago($pendiente, ['monto' => '99.50', 'numero_operacion' => 'CORREGIDO-1']);
    actualizarPago($validado, ['estado' => 'anulado', 'anulado_por' => $validado->validado_por, 'anulado_en' => now(), 'motivo_anulacion' => 'Duplicado']);

    expect($pendiente->refresh()->monto)->toBe('99.50')
        ->and($validado->refresh()->estado->value)->toBe('anulado');
});

it('frees the operation number of a rejected payment so it can be registered again', function (): void {
    Pago::factory()->rechazado()->create(['medio_pago' => MedioPago::BancoNacion, 'numero_operacion' => 'OP-777']);

    $nuevo = Pago::factory()->create(['medio_pago' => MedioPago::BancoNacion, 'numero_operacion' => 'OP-777']);

    expect($nuevo->exists)->toBeTrue()
        ->and(fn () => Pago::factory()->create(['medio_pago' => MedioPago::BancoNacion, 'numero_operacion' => 'OP-777']))
        ->toThrow(QueryException::class, 'pag_pagos_operacion_unique');
});
