<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

final class Porcentaje
{
    public static function de(int $parte, int $total): ?float
    {
        return $total === 0 ? null : round($parte * 100 / $total, 1);
    }

    public static function variacion(int $actual, ?int $anterior): ?float
    {
        return $anterior === null || $anterior === 0 ? null : round(($actual - $anterior) * 100 / $anterior, 1);
    }
}
