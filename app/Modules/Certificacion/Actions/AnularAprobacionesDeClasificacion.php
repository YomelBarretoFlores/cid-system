<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\FormaDeAprobacion;
use App\Modules\Certificacion\Models\Aprobacion;

// Voids, never deletes, the cycles a corrected placement no longer skips; only rows of that placement, so other placements keep theirs.
final readonly class AnularAprobacionesDeClasificacion
{
    public const string MOTIVO = 'Corrección del examen de clasificación';

    /**
     * @param  list<int>  $ciclosQueSeConservan  cycle numbers the placement still skips
     * @return int approvals voided now
     */
    public function __invoke(int $solicitudId, array $ciclosQueSeConservan, int $usuarioId): int
    {
        return Aprobacion::query()
            ->where(['origen_tipo' => 'solicitud_clasificacion', 'origen_id' => $solicitudId, 'forma' => FormaDeAprobacion::Clasificacion])
            ->whereNull('anulada_en')
            ->whereNotIn('ciclo_numero', $ciclosQueSeConservan)
            ->update(['anulada_en' => now(), 'anulada_por' => $usuarioId, 'motivo_anulacion' => self::MOTIVO, 'updated_at' => now()]);
    }
}
