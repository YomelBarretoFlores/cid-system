<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Audit\ContextoAuditoria;
use App\Shared\Auth\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('registers payments as pending with a public ULID', function (): void {
    $pago = Pago::factory()->create();

    expect($pago->refresh()->estado)->toBe(EstadoPago::Pendiente)
        ->and(Str::isUlid($pago->ulid))->toBeTrue()
        ->and($pago->getRouteKey())->toBe($pago->ulid);
});

it('accepts every complete state', function (string $estado, EstadoPago $esperado): void {
    expect(Pago::factory()->{$estado}()->create()->refresh()->estado)->toBe($esperado);
})->with([
    'validated' => ['validado', EstadoPago::Validado],
    'rejected' => ['rechazado', EstadoPago::Rechazado],
    'voided' => ['anulado', EstadoPago::Anulado],
]);

it('rejects payments whose state lacks its required data', function (Closure $pago, string $constraint): void {
    expect($pago)->toThrow(QueryException::class, $constraint);
})->with([
    'validated without validator' => [fn () => Pago::factory()->validado()->create(['validado_por' => null]), 'pag_pagos_datos_estado_check'],
    'validated without date' => [fn () => Pago::factory()->validado()->create(['validado_en' => null]), 'pag_pagos_datos_estado_check'],
    'rejected without reason' => [fn () => Pago::factory()->rechazado()->create(['motivo_rechazo' => '  ']), 'pag_pagos_datos_estado_check'],
    'pending with a validator' => [fn () => Pago::factory()->create(['validado_por' => User::factory(), 'validado_en' => now()]), 'pag_pagos_datos_estado_check'],
    'voided without reason' => [fn () => Pago::factory()->anulado()->create(['motivo_anulacion' => null]), 'pag_pagos_anulacion_check'],
    'validated with voiding data' => [fn () => Pago::factory()->validado()->create(['anulado_por' => User::factory(), 'anulado_en' => now(), 'motivo_anulacion' => 'x']), 'pag_pagos_anulacion_check'],
    'zero amount' => [fn () => Pago::factory()->create(['monto' => '0.00']), 'pag_pagos_monto_check'],
]);

it('rejects the same operation number twice for the same payment method', function (): void {
    Pago::factory()->create(['medio_pago' => MedioPago::BancoNacion, 'numero_operacion' => 'OP-12345']);

    expect(fn () => Pago::factory()->create(['medio_pago' => MedioPago::BancoNacion, 'numero_operacion' => ' op-12345 ']))
        ->toThrow(QueryException::class, 'pag_pagos_operacion_unique');
});

it('allows the same operation number on a different payment method', function (): void {
    Pago::factory()->create(['medio_pago' => MedioPago::BancoNacion, 'numero_operacion' => '000123']);

    expect(Pago::factory()->create(['medio_pago' => MedioPago::PagaloPe, 'numero_operacion' => '000123'])->exists)->toBeTrue();
});

it('rejects operation numbers that bypass normalization', function (): void {
    $pago = Pago::factory()->create();

    expect(fn () => DB::table('pag_pagos')->where('id', $pago->id)->update(['numero_operacion' => ' abc123']))
        ->toThrow(QueryException::class, 'pag_pagos_numero_operacion_check');
});

it('never deletes a payment', function (): void {
    $pago = Pago::factory()->create();

    expect(fn () => DB::table('pag_pagos')->where('id', $pago->id)->delete())
        ->toThrow(QueryException::class, 'pag_pagos no admite borrado');
});

it('audits who validated a payment and when, from the request context', function (): void {
    $cajero = User::factory()->create();
    $pago = Pago::factory()->create();
    app(ContextoAuditoria::class)->establecer($cajero->id, '10.10.1.20');

    $pago->update(['estado' => EstadoPago::Validado, 'validado_por' => $cajero->id, 'validado_en' => now(), 'boleta_original_verificada_por' => $cajero->id, 'boleta_original_verificada_en' => now()]);

    $registro = DB::table('aud_registros')
        ->where(['tabla' => 'pag_pagos', 'registro_id' => $pago->id, 'operacion' => 'UPDATE'])
        ->sole();

    expect($registro->usuario_id)->toBe($cajero->id)
        ->and($registro->ip)->toBe('10.10.1.20')
        ->and(json_decode((string) $registro->datos_anteriores, true)['estado'])->toBe('pendiente')
        ->and(json_decode((string) $registro->datos_nuevos, true)['estado'])->toBe('validado');
});

it('never truncates payments', function (): void {
    Pago::factory()->create();

    expect(fn () => DB::statement('TRUNCATE pag_pagos CASCADE'))
        ->toThrow(QueryException::class, 'pag_pagos no admite borrado');
});
