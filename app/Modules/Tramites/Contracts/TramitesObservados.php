<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Contracts;

interface TramitesObservados
{
    /** How many of the person's trámites wait on an observation, for the portal menu badge. */
    public function __invoke(int $personaId): int;
}
