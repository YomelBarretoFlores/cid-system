<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

use App\Modules\Docencia\Enums\EstadoSilabo;

interface EstadosDeSilabos
{
    /**
     * @param  list<int>  $grupoIds
     * @return array<int, EstadoSilabo> group id => state, only for groups that have a syllabus (the others lack one)
     */
    public function __invoke(array $grupoIds): array;
}
