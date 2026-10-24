<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

interface GruposParaTraslado
{
    /**
     * @return array<int, string> group id => "code · vacancies" of the groups that can receive this group's students
     */
    public function __invoke(int $grupoId): array;
}
