<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\ReservaDeVacante;
use App\Modules\Academico\Data\GrupoData;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class ReservarVacante implements ReservaDeVacante
{
    public function __invoke(int $grupoId): GrupoData
    {
        return Transaccion::ejecutar(function () use ($grupoId): GrupoData {
            $grupo = Grupo::query()->whereKey($grupoId)->lockForUpdate()->firstOrFail();

            if (! Grupo::query()->whereKey($grupo->id)->abiertosParaMatricula()->exists()) {
                throw new ReglaDeNegocioIncumplida('La matrícula de este grupo no está abierta.');
            }

            if ($grupo->vacantes_disponibles < 1) {
                throw new ReglaDeNegocioIncumplida('El grupo no tiene vacantes disponibles.');
            }

            $grupo->increment('matriculados');

            return GrupoData::desde($grupo);
        });
    }
}
