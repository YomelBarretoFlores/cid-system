<?php

declare(strict_types=1);

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\GrupoDeComponente;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Support\PromedioFinal;

/**
 * @return list<ComponenteDeEvaluacion>
 */
function componentesDelCiln(): array
{
    return [
        new ComponenteDeEvaluacion(1, 'auditiva', 'Comprensión auditiva', GrupoDeComponente::Habilidades),
        new ComponenteDeEvaluacion(2, 'oral', 'Expresión oral', GrupoDeComponente::Habilidades),
        new ComponenteDeEvaluacion(3, 'lectora', 'Comprensión lectora', GrupoDeComponente::Habilidades),
        new ComponenteDeEvaluacion(4, 'escrita', 'Expresión escrita', GrupoDeComponente::Habilidades),
        new ComponenteDeEvaluacion(5, 'parcial', 'Examen parcial', GrupoDeComponente::Examenes),
        new ComponenteDeEvaluacion(6, 'final', 'Examen final', GrupoDeComponente::Examenes),
    ];
}

/**
 * @param  list<string|null>  $notas  in component order, null for a blank
 * @return array<int, string>
 */
function notasDelCiln(array $notas): array
{
    return array_filter(array_combine(range(1, count($notas)), $notas), fn (?string $nota): bool => $nota !== null);
}

it('averages the skills and the exams, averages both and rounds only the final grade half up to an integer', function (array $notas, string $habilidades, string $examenes, string $final, EstadoFinal $estado): void {
    $registradas = notasDelCiln($notas);

    expect(PromedioFinal::calcular($registradas, componentesDelCiln(), '11.00'))->toBe([$final, $estado])
        ->and(PromedioFinal::promediosPorGrupo($registradas, componentesDelCiln()))->toBe([$habilidades, $examenes]);
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

it('keeps the decimals of the averages of each group for display', function (): void {
    expect(PromedioFinal::promediosPorGrupo(notasDelCiln(['10.00', '10.00', '10.00', '10.01', '10.00', '10.01']), componentesDelCiln()))->toBe(['10.00', '10.01']);
});

it('marks a student without any grade as NSP', function (array $notas): void {
    expect(PromedioFinal::calcular($notas, componentesDelCiln(), '11.00'))->toBe([null, EstadoFinal::Nsp])
        ->and(PromedioFinal::promediosPorGrupo($notas, componentesDelCiln()))->toBe([null, null]);
})->with([
    'no grade at all' => [[]],
    'grades of other components ignored' => [[9 => '20.00']],
]);
