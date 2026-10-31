<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CiclosDeGrupos;
use App\Modules\Academico\Data\GrupoData;
use App\Modules\Academico\Models\Grupo;

final readonly class ListarCiclosDeGrupos implements CiclosDeGrupos
{
    public function __invoke(array $grupoIds): array
    {
        return Grupo::query()->with('ciclo')->whereKey($grupoIds)->get()
            ->mapWithKeys(fn (Grupo $grupo): array => [$grupo->id => GrupoData::desde($grupo)])
            ->all();
    }
}
