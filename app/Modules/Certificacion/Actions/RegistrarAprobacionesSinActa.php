<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Data\AprobacionSinActa;
use App\Modules\Certificacion\Data\CicloAprobadoSinActa;
use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Certificacion\Models\Aprobacion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Insert-only and idempotent: a cycle already recorded with the same form is left as it is, so a retried event or a correction is harmless.
final readonly class RegistrarAprobacionesSinActa
{
    /**
     * @param  list<CicloAprobadoSinActa>  $ciclos
     * @return int cycles recorded now
     */
    public function __invoke(AprobacionSinActa $aprobacion, array $ciclos): int
    {
        throw_if($aprobacion->forma === FormaDeAprobacion::Matricula, ReglaDeNegocioIncumplida::class, 'Los ciclos aprobados por matrícula se registran en su acta final.');

        if ($ciclos === []) {
            return 0;
        }

        $ahora = now();

        return Aprobacion::query()->insertOrIgnore(array_map(fn (CicloAprobadoSinActa $ciclo): array => [
            'persona_id' => $aprobacion->personaId, 'idioma' => $aprobacion->idioma, 'nivel' => $ciclo->nivel->value,
            'ciclo_numero' => $ciclo->numero, 'ciclo_nombre' => $ciclo->nombre, 'horas_lectivas' => $ciclo->horasLectivas,
            'forma' => $aprobacion->forma->value, 'nota' => $aprobacion->nota, 'fecha' => $aprobacion->fecha->toDateString(),
            'origen_tipo' => $aprobacion->origenTipo, 'origen_id' => $aprobacion->origenId, 'registrado_por' => $aprobacion->registradoPor,
            'created_at' => $ahora, 'updated_at' => $ahora,
        ], $ciclos));
    }
}
