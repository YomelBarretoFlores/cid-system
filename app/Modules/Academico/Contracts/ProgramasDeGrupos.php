<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\ProgramaDelGrupo;

interface ProgramasDeGrupos
{
    /**
     * @param  list<int>  $grupoIds
     * @return array<int, ProgramaDelGrupo> group id => its language and program
     */
    public function __invoke(array $grupoIds): array;
}
