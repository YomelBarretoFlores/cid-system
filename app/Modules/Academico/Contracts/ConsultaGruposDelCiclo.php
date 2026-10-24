<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\GrupoData;

interface ConsultaGruposDelCiclo
{
    /**
     * @return list<GrupoData>
     */
    public function __invoke(int $cicloId): array;
}
