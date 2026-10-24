<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

use App\Modules\Personas\Enums\TipoVinculo;

interface ConsultaDeVinculo
{
    public function __invoke(int $personaId): TipoVinculo;
}
