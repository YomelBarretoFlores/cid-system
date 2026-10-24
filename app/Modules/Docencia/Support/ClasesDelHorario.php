<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Docencia\Data\ClaseProgramada;
use Carbon\CarbonImmutable;

final class ClasesDelHorario
{
    public const string ZONA = 'America/Lima';

    public static function hoy(): CarbonImmutable
    {
        return CarbonImmutable::today(self::ZONA);
    }

    /**
     * Classes of the weekly schedule from the period start to today or the period end, newest first.
     *
     * @return list<ClaseProgramada>
     */
    public static function hastaHoy(HorarioDeGrupo $grupo): array
    {
        $inicio = CarbonImmutable::parse($grupo->periodoInicio->toDateString(), self::ZONA);
        $fin = CarbonImmutable::parse($grupo->periodoFin->toDateString(), self::ZONA)->min(self::hoy());
        $clases = [];

        for ($dia = $fin; $dia->gte($inicio); $dia = $dia->subDay()) {
            $sesiones = array_filter($grupo->sesiones, fn (SesionDeClase $sesion): bool => $sesion->diaSemana === $dia->dayOfWeekIso);
            usort($sesiones, fn (SesionDeClase $a, SesionDeClase $b): int => strcmp($b->horaInicio, $a->horaInicio));
            array_push($clases, ...array_map(fn (SesionDeClase $sesion): ClaseProgramada => new ClaseProgramada($dia, $sesion), $sesiones));
        }

        return $clases;
    }

    public static function sesionDe(HorarioDeGrupo $grupo, CarbonImmutable $fecha, string $horaInicio): ?SesionDeClase
    {
        foreach ($grupo->sesiones as $sesion) {
            if ($sesion->diaSemana === $fecha->dayOfWeekIso && substr($sesion->horaInicio, 0, 5) === $horaInicio) {
                return $sesion;
            }
        }

        return null;
    }
}
