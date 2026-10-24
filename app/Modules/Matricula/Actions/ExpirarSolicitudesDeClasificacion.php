<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use Carbon\CarbonImmutable;

final readonly class ExpirarSolicitudesDeClasificacion
{
    public function __invoke(): int
    {
        return SolicitudClasificacion::query()
            ->where('estado', EstadoSolicitudClasificacion::PendienteEvaluacion)
            ->where('vence_en', '<', CarbonImmutable::today('America/Lima')->toDateString())
            ->update(['estado' => EstadoSolicitudClasificacion::Expirado, 'updated_at' => now()]);
    }
}
