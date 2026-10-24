<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

interface EtiquetasDePersonas
{
    /**
     * @param  list<int>  $personaIds
     * @return array<int, string>
     */
    public function __invoke(array $personaIds): array;
}
