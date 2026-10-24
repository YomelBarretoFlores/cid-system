<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

use App\Modules\Personas\Data\FichaDeEstudiante;

interface FichasDePersonas
{
    /**
     * @param  list<int>  $personaIds
     * @return array<int, FichaDeEstudiante>
     */
    public function __invoke(array $personaIds): array;
}
