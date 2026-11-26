<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use Carbon\CarbonImmutable;

interface IniciosDePeriodos
{
    /**
     * @param  list<int>  $periodoIds
     * @return array<int, CarbonImmutable> start date by period id, only the periods that exist
     */
    public function __invoke(array $periodoIds): array;
}
