<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

interface DocentesDeGrupos
{
    /**
     * @param  list<int>  $grupoIds
     * @return array<int, string> group id => full name of its assigned teacher, only for groups that have one
     */
    public function __invoke(array $grupoIds): array;
}
