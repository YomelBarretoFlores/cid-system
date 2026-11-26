<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

use App\Modules\Personas\Data\TipoDeVinculo;

interface CatalogoDeVinculos
{
    /**
     * @return list<TipoDeVinculo> every vínculo in display order, active or not, so stored codes keep their names
     */
    public function __invoke(): array;
}
