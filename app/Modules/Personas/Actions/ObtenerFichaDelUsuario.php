<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Modules\Personas\Models\Persona;

final readonly class ObtenerFichaDelUsuario implements FichaDelUsuario
{
    public function __invoke(int $userId): ?FichaDeEstudiante
    {
        $persona = Persona::query()->where('user_id', $userId)->first();

        return $persona === null ? null : new FichaDeEstudiante(
            personaId: $persona->id,
            nombres: $persona->nombres,
            nombreCompleto: $persona->nombre_completo,
            documento: "{$persona->tipo_documento->etiqueta()} {$persona->numero_documento}",
        );
    }
}
