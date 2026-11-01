<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Contracts\DenominacionDelAnio as DenominacionDelAnioContrato;
use App\Modules\Certificacion\Models\DenominacionDelAnio;

final readonly class ConsultarDenominacionDelAnio implements DenominacionDelAnioContrato
{
    public function __invoke(int $anio): ?string
    {
        $texto = DenominacionDelAnio::query()->where('anio', $anio)->value('texto');

        return is_string($texto) ? $texto : null;
    }
}
