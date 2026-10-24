<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class ActualizarAula
{
    /**
     * @param  array{nombre: string, capacidad: int, ubicacion: ?string, activo: bool}  $datos
     */
    public function __invoke(int $aulaId, array $datos): Aula
    {
        return Transaccion::ejecutar(function () use ($aulaId, $datos): Aula {
            // Same lock GuardarGrupo takes on the classroom, so a quota change cannot slip past a capacity cut.
            $aula = Aula::query()->whereKey($aulaId)->lockForUpdate()->firstOrFail();

            $mayorCupo = Grupo::query()
                ->where('aula_id', $aula->id)
                ->whereNotIn('estado', [EstadoGrupo::Cancelado, EstadoGrupo::Finalizado])
                ->orderByDesc('cupo_maximo')
                ->first();

            if ($mayorCupo !== null && $datos['capacidad'] < $mayorCupo->cupo_maximo) {
                throw new ReglaDeNegocioIncumplida("El grupo {$mayorCupo->codigo} usa esta aula con un cupo de {$mayorCupo->cupo_maximo}; la capacidad no puede ser menor.");
            }

            $aula->update($datos);

            return $aula;
        });
    }
}
