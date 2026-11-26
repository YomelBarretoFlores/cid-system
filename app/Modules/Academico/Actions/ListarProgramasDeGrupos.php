<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\ProgramasDeGrupos;
use App\Modules\Academico\Data\ProgramaDelGrupo;
use App\Modules\Academico\Models\Grupo;

final readonly class ListarProgramasDeGrupos implements ProgramasDeGrupos
{
    public function __invoke(array $grupoIds): array
    {
        return Grupo::query()
            ->with(['ciclo', 'programa'])
            ->whereKey($grupoIds)
            ->get()
            ->mapWithKeys(fn (Grupo $grupo): array => [$grupo->id => new ProgramaDelGrupo($grupo->ciclo->idioma_id, $grupo->programa->id, $grupo->programa->nombre)])
            ->all();
    }
}
