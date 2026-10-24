<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Academico\Data\FichaDeGrupo;
use App\Modules\Academico\Models\Grupo;

final readonly class DescribirGrupos implements DetalleDeGrupos
{
    public function __invoke(array $grupoIds): array
    {
        return Grupo::query()
            ->with(FichaDeGrupo::RELACIONES)
            ->whereKey($grupoIds)
            ->get()
            ->mapWithKeys(fn (Grupo $grupo): array => [$grupo->id => FichaDeGrupo::desde($grupo)])
            ->all();
    }
}
