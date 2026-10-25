<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers\Api;

use App\Modules\Matricula\Actions\ListarMatriculasDeEstudiante;
use App\Modules\Matricula\Http\Resources\MatriculaDelEstudianteResource;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListarMisMatriculas
{
    public function __construct(
        private FichaDelUsuario $fichaDelUsuario,
        private ListarMatriculasDeEstudiante $listarMatriculas,
    ) {}

    /**
     * The student's own enrollments, newest first, with their group, schedule, room or virtual link and teacher.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $ficha = ($this->fichaDelUsuario)((int) $request->user()?->getAuthIdentifier()) ?? abort(403);
        $datos = ($this->listarMatriculas)($ficha->personaId);

        return MatriculaDelEstudianteResource::collection($datos->matriculas->map(fn (Matricula $matricula): array => [
            'matricula' => $matricula,
            'grupo' => $datos->grupos[$matricula->grupo_id],
            'docente' => $datos->docentes[$matricula->grupo_id] ?? null,
        ]));
    }
}
