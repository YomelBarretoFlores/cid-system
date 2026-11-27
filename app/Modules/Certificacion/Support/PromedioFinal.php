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
        $promedio = $calculadas->delCiclo(1);

        return [$promedio, self::estado($promedio, $calculadas->sinNotas, $notaMinimaAprobatoria), $calculadas];
    }

    private static function estado(?string $promedio, bool $sinNotas, string $notaMinimaAprobatoria): EstadoFinal
    {
        if ($sinNotas || $promedio === null) {
            return EstadoFinal::Nsp;
        }

        return Nota::centesimos($promedio) >= Nota::centesimos($notaMinimaAprobatoria) ? EstadoFinal::Aprobado : EstadoFinal::Desaprobado;
    }
}
