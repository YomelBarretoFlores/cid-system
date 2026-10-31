<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\GrupoData;

interface CiclosDeGrupos
{
    /**
     * @param  list<int>  $grupoIds
     * @return array<int, GrupoData>
     */
    public function __invoke(array $grupoIds): array;
}
