<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

use App\Modules\Personas\Data\TipoDeDocumento;

interface CatalogoDeDocumentos
{
    /**
     * @return list<TipoDeDocumento> every document type in display order, active or not, so stored codes keep their names
     */
    public function __invoke(): array;
}
