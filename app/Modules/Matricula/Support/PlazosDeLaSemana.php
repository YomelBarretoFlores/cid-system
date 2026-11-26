<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Contracts\VentanasDeMatricula;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Data\VentanaDeMatricula;
use Carbon\CarbonImmutable;

// Real deadlines that fall in the current week (Lima): enrollment closings and the acta window of the student's current group.
final class PlazosDeLaSemana
{
    /**
     * @return array<int, list<string>> deadlines by ISO weekday, 1 (Monday) to 7
     */
    public static function de(CarbonImmutable $hoy, ?FichaDeGrupo $grupo): array
    {
        $lunes = $hoy->startOfWeek();
        $domingo = $hoy->endOfWeek();
        $plazos = [];
        $agregar = function (?CarbonImmutable $fecha, string $texto) use (&$plazos, $lunes, $domingo): void {
            if ($fecha instanceof CarbonImmutable && $fecha->betweenIncluded($lunes, $domingo)) {
                $plazos[$fecha->dayOfWeekIso][] = $texto;
            }
        };

        foreach (app(VentanasDeMatricula::class)() as $ventana) {
            /** @var VentanaDeMatricula $ventana */
            $agregar($ventana->cierre(), "Cierra la matrícula de {$ventana->periodo}");
        }

        $curso = $grupo instanceof FichaDeGrupo ? app(CursoDelGrupo::class)($grupo->id) : null;
        if ($curso instanceof DatosDelCurso) {
            $agregar($curso->actasInicio, "Empieza el cierre de actas de {$curso->periodo}");
            $agregar($curso->actasFin, "Último día del cierre de actas de {$curso->periodo}");
        }

        return $plazos;
    }
}
