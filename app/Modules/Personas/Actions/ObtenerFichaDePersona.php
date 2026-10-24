<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\FichaDePersona;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Modules\Personas\Models\Persona;

final readonly class ObtenerFichaDePersona implements FichaDePersona
{
    public function __invoke(int $personaId): FichaDeEstudiante
    {
        return FichaDeEstudiante::desde(Persona::query()->whereKey($personaId)->firstOrFail());
    }
}
