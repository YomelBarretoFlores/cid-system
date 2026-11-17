<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\GrupoDeComponente;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Shared\Support\Notas\Nota;

// CILN system: simple average of the skills and of the exams, then of both; only the final grade is rounded, half up to an integer.
final class PromedioFinal
{
    /**
     * @param  array<int, string>  $notas  grade per component id, blanks left out (they count as zero)
     * @param  list<ComponenteDeEvaluacion>  $componentes
     * @return array{?string, EstadoFinal}
     */
    public static function calcular(array $notas, array $componentes, string $notaMinimaAprobatoria): array
    {
        if (! self::tieneNotas($notas, $componentes)) {
            return [null, EstadoFinal::Nsp];
        }

        [$numerador, $denominador] = [0, 1];
        $grupos = self::sumasPorGrupo($notas, $componentes);

        foreach ($grupos as [$suma, $cantidad]) {
            [$numerador, $denominador] = [$numerador * $cantidad + $suma * $denominador, $denominador * $cantidad];
        }

        $entero = self::redondear($numerador, $denominador * count($grupos) * 100);
        $final = $entero * 100;

        return [Nota::deCentesimos($final), $final >= Nota::centesimos($notaMinimaAprobatoria) ? EstadoFinal::Aprobado : EstadoFinal::Desaprobado];
    }

    /**
     * Averages of the skills and of the exams with two decimals, for display only: the final grade never uses them.
     *
     * @param  array<int, string>  $notas
     * @param  list<ComponenteDeEvaluacion>  $componentes
     * @return array{?string, ?string}
     */
    public static function promediosPorGrupo(array $notas, array $componentes): array
    {
        if (! self::tieneNotas($notas, $componentes)) {
            return [null, null];
        }

        $grupos = self::sumasPorGrupo($notas, $componentes);
        $promedio = fn (GrupoDeComponente $grupo): ?string => isset($grupos[$grupo->value])
            ? Nota::deCentesimos(self::redondear(...$grupos[$grupo->value]))
            : null;

        return [$promedio(GrupoDeComponente::Habilidades), $promedio(GrupoDeComponente::Examenes)];
    }

    /**
     * @param  array<int, string>  $notas
     * @param  list<ComponenteDeEvaluacion>  $componentes
     */
    private static function tieneNotas(array $notas, array $componentes): bool
    {
        return array_any($componentes, fn (ComponenteDeEvaluacion $componente): bool => isset($notas[$componente->id]));
    }

    /**
     * @param  array<int, string>  $notas
     * @param  list<ComponenteDeEvaluacion>  $componentes
     * @return array<string, array{int, int}> sum in hundredths and number of components per group
     */
    private static function sumasPorGrupo(array $notas, array $componentes): array
    {
        $grupos = [];

        foreach ($componentes as $componente) {
            [$suma, $cantidad] = $grupos[$componente->grupo->value] ?? [0, 0];
            $grupos[$componente->grupo->value] = [$suma + Nota::centesimos($notas[$componente->id] ?? '0'), $cantidad + 1];
        }

        return $grupos;
    }

    private static function redondear(int $numerador, int $denominador): int
    {
        return intdiv($numerador * 2 + $denominador, $denominador * 2);
    }
}
