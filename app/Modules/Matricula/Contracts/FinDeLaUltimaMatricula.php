<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Contracts;

use Carbon\CarbonImmutable;

interface FinDeLaUltimaMatricula
{
    /** End date of the period of the person's latest enrollment in the language (by name), withdrawn ones included; null when there is none. */
    public function __invoke(int $personaId, string $idioma): ?CarbonImmutable;
}
