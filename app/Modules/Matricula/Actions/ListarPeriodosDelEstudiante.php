<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;

final readonly class ListarPeriodosDelEstudiante
{
    public function __construct(
        private DetalleDeGrupos $detalleDeGrupos,
        private CatalogoDePeriodos $catalogoDePeriodos,
    ) {}

    /**
     * @return array<int, string> period id => name, newest first, only periods with an active enrollment of the student
     */
    public function __invoke(int $personaId): array
    {
        $grupoIds = array_values(array_map(intval(...), Matricula::query()->where(['persona_id' => $personaId, 'estado' => EstadoMatricula::Activa])->distinct()->pluck('grupo_id')->all()));

        if ($grupoIds === []) {
            return [];
        }

        $periodoIds = array_map(fn (FichaDeGrupo $grupo): int => $grupo->periodoId, ($this->detalleDeGrupos)($grupoIds));

        return array_intersect_key(($this->catalogoDePeriodos)(), array_flip($periodoIds));
    }
}
