<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Contracts;

interface MatriculasActivasDePersona
{
    /**
     * @return array<int, string> enrollment id => group, language, cycle and period, newest first
     */
    public function __invoke(int $personaId): array;
}
