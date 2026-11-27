<?php

declare(strict_types=1);

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\NotasCalculadas;
use App\Modules\Academico\Enums\CalculoDeComponente;
use App\Modules\Academico\Enums\RedondeoDeNota;
use App\Modules\Academico\Enums\TipoDeComponente;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Support\PromedioFinal;

/**
 * @param  list<string>  $fuentes
 */
function columnaCalculada(int $id, string $codigo, array $fuentes, RedondeoDeNota $redondeo = RedondeoDeNota::Ninguno, ?int $ciclo = null): ComponenteDeEvaluacion
{
    return new ComponenteDeEvaluacion($id, $codigo, $codigo, TipoDeComponente::Calculada, CalculoDeComponente::Promedio, $redondeo, array_map(fn (string $fuente): array => ['codigo' => $fuente, 'peso' => 1], $fuentes), $ciclo);
}

/**
 * The CILN system as columns: four skills and two exams entered, their two averages, and the cycle grade rounded to an integer.
 *
 * @return list<ComponenteDeEvaluacion>
 */
function componentesDelCiln(): array
{
    return [
        new ComponenteDeEvaluacion(1, 'auditiva', 'Comprensión auditiva'),
        new ComponenteDeEvaluacion(2, 'oral', 'Expresión oral'),
        new ComponenteDeEvaluacion(3, 'lectora', 'Comprensión lectora'),
        new ComponenteDeEvaluacion(4, 'escrita', 'Expresión escrita'),
        new ComponenteDeEvaluacion(5, 'parcial', 'Examen parcial'),
        new ComponenteDeEvaluacion(6, 'final', 'Examen final'),
        columnaCalculada(7, 'promedio_habilidades', ['auditiva', 'oral', 'lectora', 'escrita']),
        columnaCalculada(8, 'promedio_examenes', ['parcial', 'final']),
        columnaCalculada(9, 'nota_final', ['promedio_habilidades', 'promedio_examenes'], RedondeoDeNota::Entero, 1),
    ];
}

/**
 * @param  list<string|null>  $notas  in column order, null for a blank
 * @return array<int, string>
 */
function notasDelCiln(array $notas): array
{
    return array_filter(array_combine(range(1, count($notas)), $notas), fn (?string $nota): bool => $nota !== null);
}

it('averages the skills and the exams, averages both and rounds only the final grade half up to an integer', function (array $notas, string $habilidades, string $examenes, string $final, EstadoFinal $estado): void {
    [$promedio, $estadoFinal, $calculadas] = PromedioFinal::calcular(notasDelCiln($notas), componentesDelCiln(), '11.00');

    expect([$promedio, $estadoFinal])->toBe([$final, $estado])
        ->and([$calculadas->valor('promedio_habilidades'), $calculadas->valor('promedio_examenes')])->toBe([$habilidades, $examenes]);
})->with([
    '13.5 rounds to 14' => [['14.00', '13.00', '14.00', '13.00', '13.00', '14.00'], '13.50', '13.50', '14.00', EstadoFinal::Aprobado],
    '10.5 rounds to 11' => [['10.00', '11.00', '10.00', '11.00', '11.00', '10.00'], '10.50', '10.50', '11.00', EstadoFinal::Aprobado],
    '13.6 rounds to 14' => [['13.60', '13.60', '13.60', '13.60', '13.60', '13.60'], '13.60', '13.60', '14.00', EstadoFinal::Aprobado],
    'exact average without rounding' => [['12.00', '14.00', '13.00', '15.00', '12.00', '13.00'], '13.50', '12.50', '13.00', EstadoFinal::Aprobado],
    'averages of 14.50 and 13.50' => [['14.00', '15.00', '14.00', '15.00', '13.00', '14.00'], '14.50', '13.50', '14.00', EstadoFinal::Aprobado],
    '10.50 reaches the passing grade' => [['11.00', '12.00', '11.00', '12.00', '10.00', '9.00'], '11.50', '9.50', '11.00', EstadoFinal::Aprobado],
    '10.4975 is not rounded twice' => [['11.00', '11.00', '11.00', '10.98', '10.00', '10.00'], '11.00', '10.00', '10.00', EstadoFinal::Desaprobado],
    'a blank counts as zero' => [['16.00', null, '16.00', '16.00', '12.00', '12.00'], '12.00', '12.00', '12.00', EstadoFinal::Aprobado],
    'all zeros is a grade' => [['0.00', null, null, null, null, null], '0.00', '0.00', '0.00', EstadoFinal::Desaprobado],
]);

it('keeps the decimals of the calculated columns without rounding for display', function (): void {
    $calculadas = NotasCalculadas::de(componentesDelCiln(), notasDelCiln(['10.00', '10.00', '10.00', '10.01', '10.00', '10.01']));

    expect([$calculadas->valor('promedio_habilidades'), $calculadas->valor('promedio_examenes')])->toBe(['10.00', '10.01']);
});

it('marks a student without any grade as NSP', function (array $notas): void {
    [$promedio, $estado, $calculadas] = PromedioFinal::calcular($notas, componentesDelCiln(), '11.00');

    expect([$promedio, $estado])->toBe([null, EstadoFinal::Nsp])
        ->and($calculadas->sinNotas)->toBeTrue()
        ->and($calculadas->valor('promedio_habilidades'))->toBeNull();
})->with([
    'no grade at all' => [[]],
    'grades of other columns ignored' => [[99 => '20.00']],
]);

it('weighs each source column when the calculation is weighted', function (): void {
    $componentes = [
        new ComponenteDeEvaluacion(1, 'parcial', 'Examen parcial'),
        new ComponenteDeEvaluacion(2, 'final', 'Examen final'),
        new ComponenteDeEvaluacion(3, 'nota', 'Nota', TipoDeComponente::Calculada, CalculoDeComponente::Ponderado, RedondeoDeNota::Ninguno, [['codigo' => 'parcial', 'peso' => 40], ['codigo' => 'final', 'peso' => 60]], 1),
    ];

    expect(PromedioFinal::calcular([1 => '10.00', 2 => '15.00'], $componentes, '11.00'))->toMatchArray(['13.00', EstadoFinal::Aprobado])
        ->and(PromedioFinal::calcular([1 => '12.00', 2 => '10.33'], $componentes, '11.00')[0])->toBe('11.00');
});

it('gives each cycle of an accelerated scheme the grade of its own column', function (): void {
    $componentes = [
        new ComponenteDeEvaluacion(1, 'primera', 'Primera quincena'),
        new ComponenteDeEvaluacion(2, 'segunda', 'Segunda quincena'),
        columnaCalculada(3, 'ciclo_1', ['primera'], RedondeoDeNota::Entero, 1),
        columnaCalculada(4, 'ciclo_2', ['segunda'], RedondeoDeNota::Entero, 2),
    ];
    $calculadas = NotasCalculadas::de($componentes, [1 => '16.40', 2 => '14.50']);

    expect([$calculadas->delCiclo(1), $calculadas->delCiclo(2)])->toBe(['16.00', '15.00'])
        ->and($calculadas->tieneCiclo(2))->toBeTrue()
        ->and(NotasCalculadas::de(componentesDelCiln(), [])->tieneCiclo(2))->toBeFalse();
});

it('takes an entered column as the cycle grade as it was typed', function (): void {
    $componentes = [new ComponenteDeEvaluacion(1, 'nota', 'Nota del ciclo', ciclo: 1)];

    expect(PromedioFinal::calcular([1 => '10.50'], $componentes, '11.00'))->toMatchArray(['10.50', EstadoFinal::Desaprobado]);
});
