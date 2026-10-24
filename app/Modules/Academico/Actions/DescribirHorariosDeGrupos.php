<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Models\Grupo;

final readonly class DescribirHorariosDeGrupos implements HorariosDeGrupos
{
    public function __invoke(array $grupoIds): array
    {
        return Grupo::query()
            ->with(HorarioDeGrupo::RELACIONES)
            ->whereKey($grupoIds)
            ->get()
            ->mapWithKeys(fn (Grupo $grupo): array => [$grupo->id => HorarioDeGrupo::desde($grupo)])
            ->all();
    }
}
