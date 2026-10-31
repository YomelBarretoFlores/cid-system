<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Docencia\Data\ClaseProgramada;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Carbon\CarbonImmutable;

final class ClasesDelHorario
{
    public const string ZONA = 'America/Lima';

    public static function hoy(): CarbonImmutable
    {
        return CarbonImmutable::today(self::ZONA);
    }

    public static function ahora(): CarbonImmutable
    {
        return CarbonImmutable::now(self::ZONA);
    }

    /**
     * Classes of the weekly schedule from the period start to now or the period end, newest first.
     * A class of today shows up once its first call opens.
     *
     * @return list<ClaseProgramada>
     */
    public static function hastaHoy(HorarioDeGrupo $grupo): array
    {
        $inicio = CarbonImmutable::parse($grupo->periodoInicio->toDateString(), self::ZONA);
        $fin = CarbonImmutable::parse($grupo->periodoFin->toDateString(), self::ZONA)->min(self::hoy());
        $ahora = self::ahora();
        $clases = [];

        for ($dia = $fin; $dia->gte($inicio); $dia = $dia->subDay()) {
            $sesiones = array_filter($grupo->sesiones, fn (SesionDeClase $sesion): bool => $sesion->diaSemana === $dia->dayOfWeekIso && VentanasDeLlamado::de($dia, $sesion)->yaAbrio($ahora));
            usort($sesiones, fn (SesionDeClase $a, SesionDeClase $b): int => strcmp($b->horaInicio, $a->horaInicio));
            array_push($clases, ...array_map(fn (SesionDeClase $sesion): ClaseProgramada => new ClaseProgramada($dia, $sesion), $sesiones));
        }

        return $clases;
    }

    /**
     * A student enrolled after the class date is not part of that class.
     *
     * @param  list<EstudianteDelGrupo>  $estudiantes
     * @return list<EstudianteDelGrupo>
     */
    public static function estudiantesDe(array $estudiantes, CarbonImmutable $dia): array
    {
        return array_values(array_filter($estudiantes, fn (EstudianteDelGrupo $estudiante): bool => $estudiante->matriculadoEl <= $dia->toDateString()));
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

    public static function fechaDeClase(string $fecha, string $horaInicio): CarbonImmutable
    {
        $dia = preg_match('/^\d{4}-\d{2}-\d{2}\z/', $fecha) === 1 ? CarbonImmutable::createFromFormat('!Y-m-d', $fecha, self::ZONA) : null;

        if (! $dia instanceof CarbonImmutable || $dia->toDateString() !== $fecha || preg_match('/^\d{2}:\d{2}\z/', $horaInicio) !== 1) {
            throw new ReglaDeNegocioIncumplida('La fecha o la hora de la clase no es válida.');
        }

        return $dia;
    }

    public static function sesionDelPeriodo(HorarioDeGrupo $grupo, CarbonImmutable $dia, string $horaInicio): SesionDeClase
    {
        if ($dia->toDateString() < $grupo->periodoInicio->toDateString() || $dia->toDateString() > $grupo->periodoFin->toDateString()) {
            throw new ReglaDeNegocioIncumplida("El {$dia->format('d/m/Y')} está fuera del periodo del grupo {$grupo->codigo}.");
        }

        return self::sesionDe($grupo, $dia, $horaInicio)
            ?? throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} no tiene clase el {$dia->format('d/m/Y')} a las {$horaInicio}.");
    }
}
