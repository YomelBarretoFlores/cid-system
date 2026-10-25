<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Academico\Enums\EstadoPeriodo;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

// The period in progress, else the latest one that already started, else the latest one.
final class PeriodoPorDefecto
{
    public static function id(): ?int
    {
        $hoy = CarbonImmutable::today('America/Lima')->toDateString();
        $periodos = DB::table('aca_periodos')->orderByDesc('fecha_inicio')->orderByDesc('id');

        $id = (clone $periodos)->where('estado', EstadoPeriodo::EnCurso->value)->value('id')
            ?? (clone $periodos)->where('fecha_inicio', '<=', $hoy)->value('id')
            ?? $periodos->value('id');

        return is_numeric($id) ? (int) $id : null;
    }
}
