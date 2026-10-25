<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Shared\Support\Notas\Nota;

// Weighted average in integer hundredths, rounded half up; a blank component counts as zero and no grade at all is NSP.
final class PromedioFinal
{
    /**
     * @param  array<int, string>  $notas  grade per component id, blanks left out
     * @param  list<ComponenteDeEvaluacion>  $componentes
     * @return array{?string, EstadoFinal}
     */
    public static function calcular(array $notas, array $componentes, string $notaMinimaAprobatoria): array
    {
        $registradas = array_filter($componentes, fn (ComponenteDeEvaluacion $componente): bool => isset($notas[$componente->id]));

        if ($registradas === []) {
            return [null, EstadoFinal::Nsp];
        }

        $sumaDePesos = array_sum(array_map(fn (ComponenteDeEvaluacion $componente): int => Nota::centesimos($componente->peso), $componentes));
        $ponderado = array_sum(array_map(
            fn (ComponenteDeEvaluacion $componente): int => Nota::centesimos($notas[$componente->id]) * Nota::centesimos($componente->peso),
            $registradas,
        ));
        $promedio = intdiv($ponderado * 2 + $sumaDePesos, $sumaDePesos * 2);

        return [Nota::deCentesimos($promedio), $promedio >= Nota::centesimos($notaMinimaAprobatoria) ? EstadoFinal::Aprobado : EstadoFinal::Desaprobado];
    }
}
