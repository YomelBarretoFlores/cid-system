<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\BloqueoDeGrupo;
use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Models\Grupo;

final readonly class BloquearGrupo implements BloqueoDeGrupo
{
    public function __invoke(int $grupoId): HorarioDeGrupo
    {
        $grupo = Grupo::query()->whereKey($grupoId)->lockForUpdate()->firstOrFail();

        return HorarioDeGrupo::desde($grupo->load(HorarioDeGrupo::RELACIONES));
    }
}
