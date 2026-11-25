<?php

declare(strict_types=1);

use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Shared\Audit\ContextoAuditoria;
use App\Shared\Auth\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

function actualizarTarifa(Tarifa $tarifa, array $cambios): int
{
    return DB::transaction(fn (): int => DB::table('pag_tarifas')->where('id', $tarifa->id)->update($cambios));
}

beforeEach(function (): void {
    $this->administrador = User::factory()->create()->givePermissionTo(PermisoPago::Configurar->value);
    $this->cajero = User::factory()->create()->givePermissionTo([PermisoPago::Ver->value, PermisoPago::Registrar->value, PermisoPago::Validar->value]);
    $this->tarifa = Tarifa::factory()->create(['monto' => '120.00', 'vigente_desde' => '2027-01-01']);
});

it('lets only users with the configuration permission manage concepts and fees', function (string $habilidad, Closure $sujeto): void {
    $sujeto = $sujeto();

    expect($this->administrador->can($habilidad, $sujeto))->toBeTrue()
        ->and($this->cajero->can($habilidad, $sujeto))->toBeFalse();
})->with([
    'list concepts' => ['viewAny', fn (): string => Concepto::class],
    'create concept' => ['create', fn (): string => Concepto::class],
    'edit concept' => ['update', fn (): Concepto => test()->tarifa->concepto],
    'list fees' => ['viewAny', fn (): string => Tarifa::class],
    'register fee' => ['create', fn (): string => Tarifa::class],
    'close fee' => ['cerrar', fn (): Tarifa => test()->tarifa],
    'delete unused fee' => ['delete', fn (): Tarifa => test()->tarifa],
]);

it('never deletes concepts nor edits or deletes fees already applied', function (): void {
    Pago::factory()->create(['concepto_id' => $this->tarifa->concepto_id, 'tarifa_id' => $this->tarifa->id]);

    expect($this->administrador->can('delete', $this->tarifa->concepto))->toBeFalse()
        ->and($this->administrador->can('update', $this->tarifa))->toBeFalse()
        ->and($this->administrador->can('delete', $this->tarifa->refresh()))->toBeFalse();
});

it('keeps the amount, link, concept and start of a fee applied by a payment', function (array $cambios): void {
    Pago::factory()->create(['concepto_id' => $this->tarifa->concepto_id, 'tarifa_id' => $this->tarifa->id, 'fecha_pago' => '2027-02-10']);

    expect(fn (): int => actualizarTarifa($this->tarifa, $cambios))->toThrow(QueryException::class, 'una tarifa aplicada a pagos');
})->with([
    'amount' => [['monto' => '99.00']],
    'link' => [['tipo_vinculo' => 'estudiante_unasam']],
    'start' => [['vigente_desde' => '2026-12-01']],
]);

it('does not close an applied fee before its last payment', function (): void {
    Pago::factory()->create(['concepto_id' => $this->tarifa->concepto_id, 'tarifa_id' => $this->tarifa->id, 'fecha_pago' => '2027-02-10']);

    expect(fn (): int => actualizarTarifa($this->tarifa, ['vigente_hasta' => '2027-02-09']))
        ->toThrow(QueryException::class, 'no puede cerrar antes de su último pago');

    actualizarTarifa($this->tarifa, ['vigente_hasta' => '2027-02-10']);
    expect($this->tarifa->refresh()->vigente_hasta->toDateString())->toBe('2027-02-10');
});

it('still lets an unused fee change', function (): void {
    actualizarTarifa($this->tarifa, ['monto' => '130.00', 'vigente_desde' => '2027-01-15']);

    expect($this->tarifa->refresh()->monto)->toBe('130.00');
});

it('audits every change to concepts and fees with the user', function (): void {
    app(ContextoAuditoria::class)->establecer($this->administrador->id, '10.0.0.9');

    $concepto = Concepto::factory()->create();
    $concepto->update(['activo' => false]);
    $tarifa = Tarifa::factory()->for($concepto)->create();
    $tarifa->update(['vigente_hasta' => '2027-12-31']);
    $tarifa->delete();

    $registros = DB::table('aud_registros')
        ->where(fn ($query) => $query
            ->where(['tabla' => 'pag_conceptos', 'registro_id' => $concepto->id])
            ->orWhere(fn ($tarifas) => $tarifas->where(['tabla' => 'pag_tarifas', 'registro_id' => $tarifa->id])))
        ->orderBy('id')
        ->get();

    expect($registros->map(fn (object $registro): string => "{$registro->tabla}:{$registro->operacion}")->all())
        ->toBe(['pag_conceptos:INSERT', 'pag_conceptos:UPDATE', 'pag_tarifas:INSERT', 'pag_tarifas:UPDATE', 'pag_tarifas:DELETE'])
        ->and($registros->pluck('usuario_id')->unique()->all())->toBe([$this->administrador->id]);
});

it('never deletes a fee or a concept that payments applied, even outside the application', function (string $tabla): void {
    Pago::factory()->create(['concepto_id' => $this->tarifa->concepto_id, 'tarifa_id' => $this->tarifa->id]);
    $id = $tabla === 'pag_tarifas' ? $this->tarifa->id : $this->tarifa->concepto_id;

    expect(fn () => DB::transaction(fn () => DB::table($tabla)->where('id', $id)->delete()))
        ->toThrow(QueryException::class, 'foreign key constraint');
})->with(['pag_tarifas', 'pag_conceptos']);
