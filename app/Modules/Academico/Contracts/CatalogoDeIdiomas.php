<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\IdiomaDelCatalogo;

interface CatalogoDeIdiomas
{
    /**
     * @return list<IdiomaDelCatalogo> every language by name, with all its cycles
     */
    public function __invoke(): array;
}
