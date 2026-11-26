<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\ProgramaDelCatalogo;

interface CatalogoDeProgramas
{
    /**
     * @return list<ProgramaDelCatalogo> every program by name, active or not
     */
    public function __invoke(): array;
}
