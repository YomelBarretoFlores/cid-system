<?php

declare(strict_types=1);

namespace App\Modules\Academico\Contracts;

use App\Modules\Academico\Enums\NivelCurricular;
use Carbon\CarbonImmutable;

interface NotaAprobatoriaDelNivel
{
    /** Passing grade of the evaluation scheme valid on the date for the language (by name) and level, the most specific first, like a group's scheme; null when none applies. */
    public function __invoke(string $idioma, NivelCurricular $nivel, CarbonImmutable $fecha): ?string;
}
