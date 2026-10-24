<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\HorarioDeGrupo;

interface HorariosDeGrupos
{
    /**
     * @param  list<int>  $grupoIds
     * @return array<int, HorarioDeGrupo>
     */
    public function __invoke(array $grupoIds): array;
}
