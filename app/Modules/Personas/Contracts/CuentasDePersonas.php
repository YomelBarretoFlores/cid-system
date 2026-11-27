<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

use App\Modules\Personas\Data\CuentaDelPortal;

interface CuentasDePersonas
{
    /**
     * Only the records that have a portal account appear in the result.
     *
     * @param  list<int>  $personaIds
     * @return array<int, CuentaDelPortal>
     */
    public function __invoke(array $personaIds): array;
}
