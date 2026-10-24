<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\ConsultaDeVinculo;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Modules\Personas\Models\Persona;

final readonly class ObtenerVinculo implements ConsultaDeVinculo
{
    public function __invoke(int $personaId): TipoVinculo
    {
        return Persona::query()->whereKey($personaId)->firstOrFail()->tipo_vinculo;
    }
}
