<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Contracts\IdiomasConNotasCerradas;
use App\Modules\Certificacion\Support\HistorialDeNotas;

final readonly class ListarIdiomasConNotasCerradas implements IdiomasConNotasCerradas
{
    public function __invoke(int $personaId): array
    {
        return array_values(HistorialDeNotas::idiomasDe($personaId));
    }
}
