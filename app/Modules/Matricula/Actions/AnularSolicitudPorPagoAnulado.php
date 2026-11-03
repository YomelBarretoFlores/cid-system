<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Matricula\Support\MovimientoDeMatricula;
use App\Shared\Support\Database\Transaccion;

// A voided payment no longer pays for a pending placement exam; an evaluated one keeps its result, as an enrollment does (decision 17).
final readonly class AnularSolicitudPorPagoAnulado
{
    public function __invoke(int $pagoId): ?SolicitudClasificacion
    {
        $personaId = SolicitudClasificacion::query()->where('pago_id', $pagoId)->value('persona_id');

        if ($personaId === null) {
            return null;
        }

        return Transaccion::ejecutar(function () use ($pagoId, $personaId): ?SolicitudClasificacion {
            MovimientoDeMatricula::bloquearPersona((int) $personaId);
            $solicitud = SolicitudClasificacion::query()->where('pago_id', $pagoId)->lockForUpdate()->first();

            if ($solicitud?->estado !== EstadoSolicitudClasificacion::PendienteEvaluacion) {
                return null;
            }

            $solicitud->update(['estado' => EstadoSolicitudClasificacion::PagoAnulado]);

            return $solicitud;
        });
    }
}
