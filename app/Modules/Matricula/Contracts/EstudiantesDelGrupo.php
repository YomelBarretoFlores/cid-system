<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Contracts;

use App\Modules\Matricula\Data\EstudianteDelGrupo;

interface EstudiantesDelGrupo
{
    /**
     * Active enrollments of the group, in alphabetical order of surnames.
     *
     * @return list<EstudianteDelGrupo>
     */
    public function __invoke(int $grupoId): array;
}
