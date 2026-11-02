<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Periodo;
use Tests\Support\DatosDelTablero;

it('gives every period made by the factory in a year a different number', function (): void {
    Periodo::factory()->count(12)->create(['anio' => 2027]);

    expect(Periodo::query()->where('anio', 2027)->orderBy('numero')->pluck('numero')->map(fn (mixed $numero): int => (int) $numero)->all())->toBe(range(1, 12));
});

it('frees the number a dashboard scenario asks for when the factory already used it', function (): void {
    $alAzar = Periodo::factory()->create(['anio' => 2027]);

    $pedido = DatosDelTablero::periodo('Abril 2027', '2027-04-01', '2027-04-30', numero: (int) $alAzar->numero);

    expect((int) $pedido->numero)->toBe((int) $alAzar->numero)
        ->and((int) $alAzar->refresh()->numero)->not->toBe((int) $pedido->numero);
});

it('says clearly when a year has no period number left instead of failing on the unique constraint', function (): void {
    Periodo::factory()->count(Periodo::NUMERO_MAXIMO)->create(['anio' => 2027]);

    expect(fn (): Periodo => Periodo::factory()->create(['anio' => 2027]))->toThrow(LogicException::class, 'No quedan números de periodo libres en 2027.');
});
