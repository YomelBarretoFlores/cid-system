<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Contracts\PeriodoEnCurso;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Periodo;
use Carbon\CarbonImmutable;

final readonly class BuscarPeriodoEnCurso implements PeriodoEnCurso
{
    public function __invoke(): ?int
    {
        $hoy = CarbonImmutable::today('America/Lima')->toDateString();
        $recientes = Periodo::query()->orderByDesc('fecha_inicio')->orderByDesc('id');

        $periodo = (clone $recientes)->where('estado', EstadoPeriodo::EnCurso)->value('id')
            ?? (clone $recientes)->whereDate('fecha_inicio', '<=', $hoy)->value('id')
            ?? $recientes->value('id');

        return $periodo === null ? null : (int) $periodo;
    }
}
