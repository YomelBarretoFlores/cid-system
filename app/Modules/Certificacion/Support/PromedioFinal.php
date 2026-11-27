<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\NotasCalculadas;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Shared\Support\Notas\Nota;

// The cycle grade is the scheme's cycle column; a student without any entered grade is NSP.
final class PromedioFinal
{
    /**
     * @param  array<int, string>  $notas  grade per column id, blanks left out (they count as zero)
     * @param  list<ComponenteDeEvaluacion>  $componentes
     * @return array{?string, EstadoFinal, NotasCalculadas}
     */
    public static function calcular(array $notas, array $componentes, string $notaMinimaAprobatoria): array
    {
        $calculadas = NotasCalculadas::de($componentes, $notas);

        return [...self::delCiclo($calculadas, 1, $notaMinimaAprobatoria), $calculadas];
    }

    /**
     * The second cycle of an accelerated group; a scheme without a second cycle grade gives it the grade of the first.
     *
     * @return array{?string, EstadoFinal}
     */
    public static function delSegundoCiclo(NotasCalculadas $calculadas, string $notaMinimaAprobatoria): array
    {
        return self::delCiclo($calculadas, $calculadas->tieneCiclo(2) ? 2 : 1, $notaMinimaAprobatoria);
    }

    /**
     * @return array{?string, EstadoFinal}
     */
    private static function delCiclo(NotasCalculadas $calculadas, int $ciclo, string $notaMinimaAprobatoria): array
    {
        $promedio = $calculadas->delCiclo($ciclo);

        if ($calculadas->sinNotas || $promedio === null) {
            return [null, EstadoFinal::Nsp];
        }

        return [$promedio, Nota::centesimos($promedio) >= Nota::centesimos($notaMinimaAprobatoria) ? EstadoFinal::Aprobado : EstadoFinal::Desaprobado];
    }
}
