<?php

declare(strict_types=1);

use App\Modules\Tablero\Support\Cifras;
use App\Modules\Tablero\Support\DiasDeClase;
use App\Modules\Tablero\Support\GraficoDeLinea;

it('writes class days as a range when three or more are consecutive and as a list otherwise', function (array $dias, string $texto): void {
    expect(DiasDeClase::texto($dias))->toBe($texto);
})->with([
    [[1, 2, 3, 4, 5], 'Lun–Vie'],
    [[1, 3, 5], 'Lun, Mié, Vie'],
    [[6, 7], 'Sáb, Dom'],
    [[6], 'Sáb'],
]);

it('draws the growth line across the box with the highest value at the top margin', function (): void {
    $trazos = GraficoDeLinea::trazos([0, 5, 10]);

    expect($trazos['linea'])->toBe('M0 180 L280 100 L560 20')
        ->and($trazos['area'])->toBe('M0 180 L280 100 L560 20 L560 200 L0 200 Z')
        ->and(GraficoDeLinea::trazos([4])['puntos'])->toBe([['x' => 280.0, 'y' => 20.0]])
        ->and(GraficoDeLinea::trazos([])['linea'])->toBe('');
});

it('writes growth with its sign and a dash when there is nothing to compare', function (): void {
    expect(Cifras::variacion(61.9))->toBe('+61.9 %')
        ->and(Cifras::variacion(-4.25))->toBe('−4.3 %')
        ->and(Cifras::variacion(null))->toBe('—')
        ->and(Cifras::soles(-550))->toBe('−S/ 5.50');
});
