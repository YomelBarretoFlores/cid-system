<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\LiberacionDeVacante;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class LiberarVacante implements LiberacionDeVacante
{
    public function __invoke(int $grupoId): void
    {
        Transaccion::ejecutar(function () use ($grupoId): void {
            $grupo = Grupo::query()->whereKey($grupoId)->lockForUpdate()->firstOrFail();

            if ($grupo->estado->esTerminal()) {
                throw new ReglaDeNegocioIncumplida("El grupo {$grupo->codigo} está {$grupo->estado->etiqueta()} y ya no admite retiros.");
            }

            $grupo->decrement('matriculados');
        });
    }
}
