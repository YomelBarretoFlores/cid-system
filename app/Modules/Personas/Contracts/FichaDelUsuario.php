<?php

declare(strict_types=1);

namespace App\Modules\Personas\Contracts;

use App\Modules\Personas\Data\FichaDeEstudiante;

interface FichaDelUsuario
{
    public function __invoke(int $userId): ?FichaDeEstudiante;
}
