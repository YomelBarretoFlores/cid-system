<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

use App\Modules\Docencia\Data\DocenteAsignado;

interface DocenteDelGrupo
{
    public function __invoke(int $grupoId): ?DocenteAsignado;
}
