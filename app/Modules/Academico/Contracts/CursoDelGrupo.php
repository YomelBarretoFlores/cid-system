<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\DatosDelCurso;

interface CursoDelGrupo
{
    public function __invoke(int $grupoId): ?DatosDelCurso;
}
