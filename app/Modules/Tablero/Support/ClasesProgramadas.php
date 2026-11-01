<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use Carbon\CarbonImmutable;

// Every class of the weekly schedule from the period start to its end, counted like Docencia's ClasesDelHorario::delPeriodo.
final class ClasesProgramadas
{
    public static function de(HorarioDeGrupo $grupo): int
    {
        $fin = CarbonImmutable::parse($grupo->periodoFin->toDateString());
        $total = 0;

        for ($dia = CarbonImmutable::parse($grupo->periodoInicio->toDateString()); $dia->lte($fin); $dia = $dia->addDay()) {
            $total += count(array_filter($grupo->sesiones, fn (SesionDeClase $sesion): bool => $sesion->diaSemana === $dia->dayOfWeekIso));
        }

        return $total;
    }
}
