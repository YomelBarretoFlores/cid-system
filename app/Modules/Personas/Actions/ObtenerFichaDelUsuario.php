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

        return $persona === null ? null : FichaDeEstudiante::desde($persona);
    }
}
