<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

interface ConsultaDeVinculo
{
    /** The code of the person's vínculo in the catalog. */
    public function __invoke(int $personaId): string;
}
