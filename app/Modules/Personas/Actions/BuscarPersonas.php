<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\BusquedaDePersonas;
use App\Modules\Personas\Models\Persona;

final readonly class BuscarPersonas implements BusquedaDePersonas
{
    public function __invoke(string $termino, int $limite = 50): array
    {
        if (trim($termino) === '') {
            return [];
        }

        return Persona::query()
            ->with('tipoDeDocumento')
            ->coincideCon($termino)
            ->orderBy('apellido_paterno')
            ->orderBy('apellido_materno')
            ->orderBy('nombres')
            ->limit($limite)
            ->get()
            ->mapWithKeys(fn (Persona $persona): array => [$persona->id => $persona->etiqueta])
            ->all();
    }
}
