<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use App\Modules\Personas\Models\Persona;

final readonly class EtiquetarPersonas implements EtiquetasDePersonas
{
    public function __invoke(array $personaIds): array
    {
        return Persona::query()
            ->with('tipoDeDocumento')
            ->whereKey($personaIds)
            ->get()
            ->mapWithKeys(fn (Persona $persona): array => [$persona->id => $persona->etiqueta])
            ->all();
    }
}
