<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Data\ModalidadDelCatalogo;

interface CatalogoDeModalidades
{
    /**
     * @return list<ModalidadDelCatalogo> every modality in display order, active or not, so stored codes keep their names
     */
    public function __invoke(): array;
}
