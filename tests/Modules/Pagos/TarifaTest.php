<?php

declare(strict_types=1);

use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Tarifa;
use Carbon\CarbonImmutable;
use Illuminate\Database\QueryException;

it('rejects fees without a positive amount or with an inverted validity', function (array $attributes, string $constraint): void {
    expect(fn () => Tarifa::factory()->create($attributes))->toThrow(QueryException::class, $constraint);
})->with([
    'zero amount' => [['monto' => '0.00'], 'pag_tarifas_monto_check'],
    'validity ending before it starts' => [['vigente_desde' => '2027-06-01', 'vigente_hasta' => '2027-05-31'], 'pag_tarifas_vigencia_check'],
]);

it('rejects overlapping fees for the same concept and university link', function (string $desde, ?string $hasta): void {
    $concepto = Concepto::factory()->create();
    Tarifa::factory()->for($concepto)->create(['vigente_desde' => '2027-01-01', 'vigente_hasta' => '2027-06-30']);

    expect(fn () => Tarifa::factory()->for($concepto)->create(['vigente_desde' => $desde, 'vigente_hasta' => $hasta]))
        ->toThrow(QueryException::class, 'pag_tarifas_sin_superposicion');
})->with([
    'starts inside' => ['2027-06-30', '2027-12-31'],
    'open-ended starting inside' => ['2027-03-01', null],
    'covers it entirely' => ['2026-12-01', '2027-12-31'],
]);

it('allows consecutive fees and different fees per university link', function (): void {
    $concepto = Concepto::factory()->create();
    Tarifa::factory()->for($concepto)->create(['vigente_desde' => '2027-01-01', 'vigente_hasta' => '2027-06-30']);

    $siguiente = Tarifa::factory()->for($concepto)->create(['vigente_desde' => '2027-07-01', 'vigente_hasta' => null]);
    $estudiantes = Tarifa::factory()->for($concepto)->create([
        'tipo_vinculo' => 'estudiante_unasam',
        'monto' => '80.00',
        'vigente_desde' => '2027-01-01',
    ]);

    expect($siguiente->exists)->toBeTrue()
        ->and($estudiantes->tipo_vinculo)->toBe('estudiante_unasam');
});

it('finds the fee in force on a given date', function (): void {
    $concepto = Concepto::factory()->create();
    Tarifa::factory()->for($concepto)->create(['monto' => '150.00', 'vigente_desde' => '2027-01-01', 'vigente_hasta' => '2027-06-30']);
    Tarifa::factory()->for($concepto)->create(['monto' => '170.00', 'vigente_desde' => '2027-07-01', 'vigente_hasta' => null]);

    $enMarzo = $concepto->tarifas()->vigenteEn(CarbonImmutable::parse('2027-03-15'))->sole();
    $enDiciembre = $concepto->tarifas()->vigenteEn(CarbonImmutable::parse('2027-12-01'))->sole();

    expect($enMarzo->monto)->toBe('150.00')
        ->and($enDiciembre->monto)->toBe('170.00')
        ->and($concepto->tarifas()->vigenteEn(CarbonImmutable::parse('2026-12-31'))->exists())->toBeFalse();
});
