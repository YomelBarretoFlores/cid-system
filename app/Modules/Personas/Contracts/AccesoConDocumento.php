<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

use App\Modules\Personas\Data\CuentaDelPortal;

// The caller authorizes and validates the password; this only gives the record its document as username.
interface AccesoConDocumento
{
    public function __invoke(int $personaId, string $contrasena): CuentaDelPortal;
}
