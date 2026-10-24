<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\CerrarTarifa;
use App\Modules\Pagos\Actions\RegistrarTarifa;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $this->concepto = Concepto::factory()->create();
    $this->vigente = Tarifa::factory()->for($this->concepto)->create([
        'tipo_vinculo' => TipoVinculo::PublicoGeneral, 'monto' => '150.00', 'vigente_desde' => '2027-01-01',
    ]);
});

function fecha(string $fecha): CarbonImmutable
{
    return CarbonImmutable::parse($fecha);
}

function registrarTarifa(string $desde, string $monto = '170.00', TipoVinculo $vinculo = TipoVinculo::PublicoGeneral, ?string $hasta = null): Tarifa
{
    return app(RegistrarTarifa::class)(test()->concepto->id, $vinculo, $monto, fecha($desde), $hasta === null ? null : fecha($hasta));
}

it('registers a new fee and closes the open one the day before', function (): void {
    $nueva = registrarTarifa('2027-07-01');

    expect($nueva->refresh())
        ->monto->toBe('170.00')
        ->vigente_desde->toDateString()->toBe('2027-07-01')
        ->vigente_hasta->toBeNull()
        ->and($this->vigente->refresh()->vigente_hasta->toDateString())->toBe('2027-06-30')
        ->and(Tarifa::query()->vigenteEn(fecha('2027-06-30'))->where('tipo_vinculo', 'publico_general')->sole()->is($this->vigente))->toBeTrue()
        ->and(Tarifa::query()->vigenteEn(fecha('2027-07-01'))->where('tipo_vinculo', 'publico_general')->sole()->is($nueva))->toBeTrue();
});

it('leaves fees of other links untouched', function (): void {
    $estudiantes = Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'vigente_desde' => '2027-01-01']);

    registrarTarifa('2027-07-01');

    expect($estudiantes->refresh()->vigente_hasta)->toBeNull();
});

it('refuses a fee whose validity overlaps another one', function (string $desde, ?string $hasta, string $mensaje): void {
    Tarifa::factory()->for($this->concepto)->create(['tipo_vinculo' => TipoVinculo::EstudianteUnasam, 'vigente_desde' => '2027-03-01', 'vigente_hasta' => '2027-03-31']);

    expect(fn (): Tarifa => registrarTarifa($desde, vinculo: TipoVinculo::EstudianteUnasam, hasta: $hasta))
        ->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
    expect(Tarifa::query()->count())->toBe(2);
})->with([
    'inside a closed fee' => ['2027-03-15', '2027-04-30', 'La vigencia se superpone con otra tarifa de este concepto y vínculo.'],
    'open fee covering a later closed one' => ['2027-02-01', null, 'La vigencia se superpone con otra tarifa de este concepto y vínculo.'],
]);

it('refuses to replace the open fee from its own start or earlier', function (): void {
    expect(fn (): Tarifa => registrarTarifa('2027-01-01'))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La nueva tarifa debe empezar después del 01/01/2027, inicio de la tarifa vigente.');
    expect($this->vigente->refresh()->vigente_hasta)->toBeNull();
});

it('refuses to close the open fee before a payment that applied it', function (): void {
    Pago::factory()->create(['concepto_id' => $this->concepto->id, 'tarifa_id' => $this->vigente->id, 'fecha_pago' => '2027-07-15']);

    expect(fn (): Tarifa => registrarTarifa('2027-07-01'))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'La tarifa se aplicó a un pago del 15/07/2027; no puede cerrarse antes de esa fecha.');
    expect($this->vigente->refresh()->vigente_hasta)->toBeNull()
        ->and(Tarifa::query()->count())->toBe(1);
});

it('closes a fee on a date and repeats harmlessly', function (): void {
    app(CerrarTarifa::class)($this->vigente->id, fecha('2027-12-31'));
    app(CerrarTarifa::class)($this->vigente->id, fecha('2027-12-31'));

    expect($this->vigente->refresh()->vigente_hasta->toDateString())->toBe('2027-12-31');
});

it('refuses to close a fee before it starts or before its last payment', function (string $hasta, string $mensaje): void {
    Pago::factory()->create(['concepto_id' => $this->concepto->id, 'tarifa_id' => $this->vigente->id, 'fecha_pago' => '2027-05-20']);

    expect(fn () => app(CerrarTarifa::class)($this->vigente->id, fecha($hasta)))->toThrow(ReglaDeNegocioIncumplida::class, $mensaje);
    expect($this->vigente->refresh()->vigente_hasta)->toBeNull();
})->with([
    'before its start' => ['2026-12-31', 'El cierre no puede ser anterior al inicio de la vigencia (01/01/2027).'],
    'before its last payment' => ['2027-05-19', 'La tarifa se aplicó a un pago del 20/05/2027; no puede cerrarse antes de esa fecha.'],
]);
