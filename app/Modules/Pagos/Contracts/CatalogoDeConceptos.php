<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Data\ConceptoDelCatalogo;

interface CatalogoDeConceptos
{
    /**
     * @return list<ConceptoDelCatalogo> every payment concept by name, active or not
     */
    public function __invoke(): array;
}
