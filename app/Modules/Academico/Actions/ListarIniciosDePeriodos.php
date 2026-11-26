<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\IniciosDePeriodos;
use App\Modules\Academico\Models\Periodo;

final readonly class ListarIniciosDePeriodos implements IniciosDePeriodos
{
    public function __invoke(array $periodoIds): array
    {
        if ($periodoIds === []) {
            return [];
        }

        return Periodo::query()
            ->whereIntegerInRaw('id', array_values(array_unique($periodoIds)))
            ->get(['id', 'fecha_inicio'])
            ->mapWithKeys(fn (Periodo $periodo): array => [$periodo->id => $periodo->fecha_inicio])
            ->all();
    }
}
