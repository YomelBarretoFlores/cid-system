<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Contracts;

interface ProgramasDeLaPersona
{
    /**
     * @return array<int, string> program id => name, taken from the latest enrollment in each language
     */
    public function __invoke(int $personaId): array;
}
