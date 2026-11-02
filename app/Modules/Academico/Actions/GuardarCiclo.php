<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Data\DatosDeCiclo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class GuardarCiclo
{
    public function __invoke(int $idiomaId, DatosDeCiclo $datos, ?int $cicloId = null): Ciclo
    {
        return Transaccion::ejecutar(function () use ($idiomaId, $datos, $cicloId): Ciclo {
            // The row lock also waits for a group being created on this cycle: its foreign key check holds a key-share lock.
            $ciclo = $cicloId === null
                ? new Ciclo(['idioma_id' => $idiomaId])
                : Ciclo::query()->where('idioma_id', $idiomaId)->whereKey($cicloId)->lockForUpdate()->firstOrFail();

            if ($ciclo->exists && $this->cambiaElPlan($ciclo, $datos) && Grupo::query()->where('ciclo_id', $ciclo->id)->exists()) {
                throw new ReglaDeNegocioIncumplida("El ciclo {$ciclo->nombre} ya tiene grupos: su número, nivel y horas lectivas no pueden cambiar.");
            }

            $ciclo->fill([
                'numero' => $datos->numero,
                'nivel' => $datos->nivel,
                'nombre' => $datos->nombre,
                'horas_lectivas' => $datos->horasLectivas,
                'activo' => $datos->activo,
            ])->save();

            return $ciclo;
        });
    }

    private function cambiaElPlan(Ciclo $ciclo, DatosDeCiclo $datos): bool
    {
        return $ciclo->numero !== $datos->numero || $ciclo->nivel !== $datos->nivel || $ciclo->horas_lectivas !== $datos->horasLectivas;
    }
}
