<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class CambiarEstadoGrupo
{
    public function __invoke(int $grupoId, EstadoGrupo $destino): Grupo
    {
        return Transaccion::ejecutar(function () use ($grupoId, $destino): Grupo {
            $grupo = Grupo::query()->whereKey($grupoId)->lockForUpdate()->firstOrFail();

            if ($grupo->estado === $destino) {
                return $grupo;
            }

            $grupo->estado->asegurarTransicionA($destino);

            if ($destino === EstadoGrupo::Cancelado && $grupo->matriculados > 0) {
                throw new ReglaDeNegocioIncumplida("No se puede cancelar un grupo con {$grupo->matriculados} estudiantes matriculados.");
            }

            $grupo->update(['estado' => $destino]);

            return $grupo;
        });
    }
}
