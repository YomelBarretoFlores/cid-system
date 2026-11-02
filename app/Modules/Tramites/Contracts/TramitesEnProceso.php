<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Contracts;

interface TramitesEnProceso
{
    /** How many of the person's trámites the CILN is working on, for the portal home. */
    public function __invoke(int $personaId): int;
}
