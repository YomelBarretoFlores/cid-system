<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Contracts\EstadosDeSilabos;
use App\Modules\Docencia\Models\Silabo;

final readonly class ConsultarEstadosDeSilabos implements EstadosDeSilabos
{
    public function __invoke(array $grupoIds): array
    {
        return Silabo::query()->whereIn('grupo_id', $grupoIds)->get(['grupo_id', 'estado'])
            ->mapWithKeys(fn (Silabo $silabo): array => [$silabo->grupo_id => $silabo->estado])
            ->all();
    }
}
