<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\FichaDeGrupo;

interface DetalleDeGrupos
{
    /**
     * @param  list<int>  $grupoIds
     * @return array<int, FichaDeGrupo>
     */
    public function __invoke(array $grupoIds): array;
}
