<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

interface FotoDePersona
{
    // The person's photo as a data URI for printed documents, or null when there is none; never the storage path.
    public function __invoke(int $personaId): ?string;
}
