<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use App\Modules\Matricula\Data\CeldaDelHorario;
use App\Modules\Matricula\Data\GrupoDelHorario;
use App\Modules\Matricula\Data\HorarioSemanal;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Support\CuadriculaDelHorario;
use App\Modules\Matricula\Support\MatriculasDelEstudiante;

// The SGA "Horario personalizado" of the period's active enrollments: courses numbered [01], [02]… with one color each, and the week grid.
final readonly class ArmarHorarioSemanal
{
    public function __construct(private CuadriculaDelHorario $cuadricula) {}

    public function __invoke(MatriculasDelEstudiante $datos): HorarioSemanal
    {
        $fichas = array_values($datos->matriculas
            ->filter(fn (Matricula $matricula): bool => $matricula->estado === EstadoMatricula::Activa && isset($datos->grupos[$matricula->grupo_id]))
            ->map(fn (Matricula $matricula): FichaDeGrupo => $datos->grupos[$matricula->grupo_id])
            ->unique(fn (FichaDeGrupo $grupo): int => $grupo->id)
            ->sortBy(fn (FichaDeGrupo $grupo): string => $grupo->codigo)
            ->all());
        $grupos = [];
        $clases = [];

        foreach ($fichas as $indice => $ficha) {
            $grupos[] = $this->grupo($ficha, $indice + 1, $datos->docentes[$ficha->id] ?? 'Por asignar');
            $celda = new CeldaDelHorario($grupos[$indice]->numero, $ficha->aulaCodigo ?? ($ficha->enlaceVirtual === null ? '—' : 'VIRTUAL'), $indice + 1);
            array_push($clases, ...array_map(fn (SesionDeClase $sesion): array => [$celda, $sesion], $ficha->sesiones));
        }

        return new HorarioSemanal($this->cuadricula->dias($clases), $this->cuadricula->franjas($clases), $grupos);
    }

    private function grupo(FichaDeGrupo $ficha, int $posicion, string $docente): GrupoDelHorario
    {
        return new GrupoDelHorario(
            sprintf('%02d', $posicion), $posicion, $ficha->codigo, $ficha->idioma, $ficha->cicloNumero, $ficha->nivel, $ficha->modalidad,
            $docente, $ficha->aula, $ficha->enlaceVirtual, $ficha->horarioCompacto(),
        );
    }
}
