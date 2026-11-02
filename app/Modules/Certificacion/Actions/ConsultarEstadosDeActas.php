<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Contracts\EstadosDeActas;
use App\Modules\Certificacion\Models\Acta;

final readonly class ConsultarEstadosDeActas implements EstadosDeActas
{
    public function __invoke(array $grupoIds): array
    {
        return Acta::query()->whereIn('grupo_id', $grupoIds)->get(['grupo_id', 'estado'])
            ->mapWithKeys(fn (Acta $acta): array => [(int) $acta->getAttribute('grupo_id') => $acta->estado])
            ->all();
    }
}
