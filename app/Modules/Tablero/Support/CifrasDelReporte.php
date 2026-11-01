<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

// Whole percentages rounded half up with integer arithmetic, like the attendance contract, and hours with two decimals.
final class CifrasDelReporte
{
    public static function porcentaje(int $parte, int $total): ?int
    {
        return $total === 0 ? null : intdiv($parte * 200 + $total, $total * 2);
    }

    public static function horas(int $minutos): float
    {
        return round($minutos / 60, 2);
    }
}
