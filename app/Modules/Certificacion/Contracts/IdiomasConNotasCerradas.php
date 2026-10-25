<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

interface IdiomasConNotasCerradas
{
    /**
     * @return list<string> languages in which the student has at least one closed acta, by name
     */
    public function __invoke(int $personaId): array;
}
