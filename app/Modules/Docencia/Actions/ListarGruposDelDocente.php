<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Models\CargaAcademica;

// A teacher's current groups (not cancelled or finished), latest period first, for the portal page and the API.
final readonly class ListarGruposDelDocente
{
    public function __construct(private HorariosDeGrupos $horariosDeGrupos) {}

    /**
     * @return list<HorarioDeGrupo>
     */
    public function __invoke(int $docenteId): array
    {
        $grupoIds = array_values(array_map(intval(...), CargaAcademica::query()->where('docente_id', $docenteId)->pluck('grupo_id')->all()));

        return array_values(collect(($this->horariosDeGrupos)($grupoIds))
            ->reject(fn (HorarioDeGrupo $grupo): bool => $grupo->estado->esTerminal())
            ->sortBy([['periodoInicio', 'desc'], ['codigo', 'asc']])
            ->all());
    }
}
