<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Matricula\Data\FilaDelHorario;
use App\Modules\Matricula\Data\GrupoDelHorario;
use App\Modules\Matricula\Data\HorarioSemanal;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Support\MatriculasDelEstudiante;

// The week of the period's active enrollments: one row per class time, one column per day with classes.
final readonly class ArmarHorarioSemanal
{
    public function __invoke(MatriculasDelEstudiante $datos): HorarioSemanal
    {
        $grupos = array_values($datos->matriculas
            ->filter(fn (Matricula $matricula): bool => $matricula->estado === EstadoMatricula::Activa && isset($datos->grupos[$matricula->grupo_id]))
            ->map(fn (Matricula $matricula): FichaDeGrupo => $datos->grupos[$matricula->grupo_id])
            ->unique(fn (FichaDeGrupo $grupo): int => $grupo->id)
            ->all());
        $dias = [];
        $celdas = [];

        foreach ($grupos as $grupo) {
            foreach ($grupo->sesiones as $sesion) {
                $dias[$sesion->diaSemana] = $sesion->dia();
                $celdas[$this->horas($sesion)][$sesion->diaSemana][] = $grupo->codigo;
            }
        }

        ksort($dias);
        ksort($celdas);

        return new HorarioSemanal(
            $dias,
            array_map(fn (string $horas, array $porDia): FilaDelHorario => new FilaDelHorario($horas, $porDia), array_keys($celdas), $celdas),
            array_map(fn (FichaDeGrupo $grupo): GrupoDelHorario => new GrupoDelHorario(
                $grupo->codigo, $grupo->idioma, $grupo->cicloNumero, $datos->docentes[$grupo->id] ?? 'Por asignar', $grupo->aula, $grupo->enlaceVirtual, $grupo->horarioCompacto(),
            ), $grupos),
        );
    }

    private function horas(SesionDeClase $sesion): string
    {
        return substr($sesion->horaInicio, 0, 5).' – '.substr($sesion->horaFin, 0, 5);
    }
}
