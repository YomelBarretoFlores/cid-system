<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Periodo;
use App\Shared\Support\Database\Transaccion;

final readonly class CambiarEstadoPeriodo
{
    public function __invoke(int $periodoId, EstadoPeriodo $destino): Periodo
    {
        return Transaccion::ejecutar(function () use ($periodoId, $destino): Periodo {
            $periodo = Periodo::query()->whereKey($periodoId)->lockForUpdate()->firstOrFail();

            if ($periodo->estado === $destino) {
                return $periodo;
            }

            $periodo->estado->asegurarTransicionA($destino);
            $periodo->update(['estado' => $destino]);

            return $periodo;
        });
    }
}
