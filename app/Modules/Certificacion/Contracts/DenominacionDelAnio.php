<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

// The official name of a year as documents print it, or null when the CILN has not registered one.
interface DenominacionDelAnio
{
    public function __invoke(int $anio): ?string;
}
