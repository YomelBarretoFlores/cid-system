<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

interface MatriculasCalificadas
{
    /**
     * @param  list<int>  $matriculaIds
     * @return list<int> those with a final grade in a closed acta
     */
    public function __invoke(array $matriculaIds): array;
}
