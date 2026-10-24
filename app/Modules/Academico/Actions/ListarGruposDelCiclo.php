<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\ConsultaGruposDelCiclo;
use App\Modules\Academico\Data\GrupoData;
use App\Modules\Academico\Models\Grupo;

final readonly class ListarGruposDelCiclo implements ConsultaGruposDelCiclo
{
    public function __invoke(int $cicloId): array
    {
        return array_values(Grupo::query()
            ->with('ciclo')
            ->where('ciclo_id', $cicloId)
            ->get()
            ->map(GrupoData::desde(...))
            ->all());
    }
}
