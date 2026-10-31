<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Support\MatriculasDelEstudiante;
use App\Modules\Personas\Contracts\FichaDelUsuario;

final readonly class BuscarMatriculasDelPeriodo
{
    public function __construct(
        private FichaDelUsuario $fichaDelUsuario,
        private ListarMatriculasDeEstudiante $listarMatriculas,
    ) {}

    // A period without an enrollment of the student answers 404, like BuscarMatriculaDelEstudiante.
    public function __invoke(int $periodoId, int $userId): MatriculasDelEstudiante
    {
        $personaId = (($this->fichaDelUsuario)($userId) ?? abort(403))->personaId;
        $datos = ($this->listarMatriculas)($personaId);
        $grupos = array_filter($datos->grupos, fn (FichaDeGrupo $grupo): bool => $grupo->periodoId === $periodoId);
        // Withdrawn enrollments stay out of the reports, as they stay out of the acta.
        $matriculas = $datos->matriculas->filter(fn (Matricula $matricula): bool => $matricula->estado === EstadoMatricula::Activa && isset($grupos[$matricula->grupo_id]))->values();

        abort_if($matriculas->isEmpty(), 404);
        $grupos = array_intersect_key($grupos, array_flip($matriculas->pluck('grupo_id')->all()));

        return new MatriculasDelEstudiante($matriculas, $grupos, array_intersect_key($datos->docentes, $grupos));
    }
}
