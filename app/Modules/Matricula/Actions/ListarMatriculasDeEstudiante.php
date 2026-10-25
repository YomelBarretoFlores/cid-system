<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Support\MatriculasDelEstudiante;

// A student's own enrollments with their group and teacher, for the portal page and the API.
final readonly class ListarMatriculasDeEstudiante
{
    public function __construct(
        private DetalleDeGrupos $detalleDeGrupos,
        private DocentesDeGrupos $docentesDeGrupos,
    ) {}

    public function __invoke(int $personaId): MatriculasDelEstudiante
    {
        $matriculas = Matricula::query()
            ->where('persona_id', $personaId)
            ->orderByDesc('matriculado_en')
            ->orderByDesc('id')
            ->get();
        $grupoIds = array_values(array_map(intval(...), $matriculas->pluck('grupo_id')->unique()->all()));

        return new MatriculasDelEstudiante($matriculas, ($this->detalleDeGrupos)($grupoIds), ($this->docentesDeGrupos)($grupoIds));
    }
}
