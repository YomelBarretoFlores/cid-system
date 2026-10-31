<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

use App\Modules\Docencia\Data\AsistenciaDelEstudiante;

interface AsistenciaDeMatricula
{
    // The classes with the enrollment's attendance, oldest first, against the classes the group's schedule programs in the whole period.
    public function __invoke(int $matriculaId, int $grupoId): AsistenciaDelEstudiante;
}
