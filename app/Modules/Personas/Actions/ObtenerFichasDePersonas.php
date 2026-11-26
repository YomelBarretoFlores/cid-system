<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Modules\Personas\Models\Persona;

final readonly class ObtenerFichasDePersonas implements FichasDePersonas
{
    public function __invoke(array $personaIds): array
    {
        return Persona::query()
            ->with('vinculo')
            ->whereKey($personaIds)
            ->get()
            ->mapWithKeys(fn (Persona $persona): array => [$persona->id => FichaDeEstudiante::desde($persona)])
            ->all();
    }
}
