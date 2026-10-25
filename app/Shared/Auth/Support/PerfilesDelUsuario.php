<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;

// The portal profiles an account holds; one account can be both a student and a teacher.
final readonly class PerfilesDelUsuario
{
    public function __construct(
        private FichaDelUsuario $fichaDelUsuario,
        private DocenteDelUsuario $docenteDelUsuario,
    ) {}

    /**
     * @return array{estudiante: bool, docente: bool}
     */
    public function de(int $userId): array
    {
        return [
            'estudiante' => ($this->fichaDelUsuario)($userId) instanceof FichaDeEstudiante,
            'docente' => ($this->docenteDelUsuario)($userId) !== null,
        ];
    }
}
