<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\DatosDelCurso;

interface CursosDelPeriodo
{
    /**
     * @return list<DatosDelCurso> groups of the period that were not cancelled, by language, cycle and code
     */
    public function __invoke(int $periodoId): array;
}
