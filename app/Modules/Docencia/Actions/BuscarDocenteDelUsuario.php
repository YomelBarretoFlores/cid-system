<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;

final readonly class BuscarDocenteDelUsuario implements DocenteDelUsuario
{
    public function __construct(private FichaDelUsuario $fichaDelUsuario) {}

    public function __invoke(int $userId): ?int
    {
        $ficha = ($this->fichaDelUsuario)($userId);

        if (! $ficha instanceof FichaDeEstudiante) {
            return null;
        }

        $docenteId = Docente::query()->where('persona_id', $ficha->personaId)->value('id');

        return $docenteId === null ? null : (int) $docenteId;
    }
}
