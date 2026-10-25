<?php

declare(strict_types=1);

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Support\PromedioFinal;

it('averages the grades by weight, rounds half up to hundredths and compares with the passing grade', function (array $notas, array $pesos, ?string $promedio, EstadoFinal $estado): void {
    $componentes = array_map(fn (int $id, string $peso): ComponenteDeEvaluacion => new ComponenteDeEvaluacion($id, "c{$id}", "Componente {$id}", $peso), array_keys($pesos), $pesos);

    expect(PromedioFinal::calcular($notas, $componentes, '11.00'))->toBe([$promedio, $estado]);
})->with([
    'weighted' => [[1 => '15.00', 2 => '12.00', 3 => '18.00'], [1 => '40.00', 2 => '40.00', 3 => '20.00'], '14.40', EstadoFinal::Aprobado],
    'blank counts as zero' => [[1 => '15.00', 3 => '18.00'], [1 => '40.00', 2 => '40.00', 3 => '20.00'], '9.60', EstadoFinal::Desaprobado],
    'exactly the passing grade' => [[1 => '11.00', 2 => '11.00', 3 => '11.00'], [1 => '40.00', 2 => '40.00', 3 => '20.00'], '11.00', EstadoFinal::Aprobado],
    'just below the passing grade' => [[1 => '10.99', 2 => '10.99', 3 => '10.99'], [1 => '40.00', 2 => '40.00', 3 => '20.00'], '10.99', EstadoFinal::Desaprobado],
    'half a hundredth rounds up' => [[1 => '10.01', 2 => '11.00'], [1 => '50.00', 2 => '50.00'], '10.51', EstadoFinal::Desaprobado],
    'weights not adding to 100' => [[1 => '20.00', 2 => '10.00'], [1 => '30.00', 2 => '30.00'], '15.00', EstadoFinal::Aprobado],
    'all zeros is a grade' => [[1 => '0.00'], [1 => '40.00', 2 => '60.00'], '0.00', EstadoFinal::Desaprobado],
    'no grade at all' => [[], [1 => '40.00', 2 => '60.00'], null, EstadoFinal::Nsp],
    'grades of other components ignored' => [[9 => '20.00'], [1 => '100.00'], null, EstadoFinal::Nsp],
]);
