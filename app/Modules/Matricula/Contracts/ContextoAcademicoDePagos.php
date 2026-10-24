<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Contracts;

use App\Modules\Matricula\Data\ContextoAcademico;

interface ContextoAcademicoDePagos
{
    /**
     * @param  list<int>  $pagoIds
     * @return array<int, ContextoAcademico> only the payments that paid an enrollment or a placement request
     */
    public function __invoke(array $pagoIds): array;
}
