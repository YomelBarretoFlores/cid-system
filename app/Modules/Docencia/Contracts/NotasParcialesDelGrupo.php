<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Contracts;

interface NotasParcialesDelGrupo
{
    /**
     * @return array<int, array<int, string>> enrollment id => component id => recorded grade; blank grades are left out
     */
    public function __invoke(int $grupoId): array;
}
