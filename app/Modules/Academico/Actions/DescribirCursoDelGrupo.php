<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Data\DatosDelCurso;
use App\Modules\Academico\Models\Grupo;

final readonly class DescribirCursoDelGrupo implements CursoDelGrupo
{
    public function __invoke(int $grupoId): ?DatosDelCurso
    {
        $grupo = Grupo::query()->with(DatosDelCurso::RELACIONES)->find($grupoId);

        return $grupo === null ? null : DatosDelCurso::desde($grupo);
    }
}
