<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use Carbon\CarbonImmutable;

// Week n of a period runs from the period start plus 7(n-1) days; the last one ends on the period end.
final class SemanasDelPeriodo
{
    public static function contar(CarbonImmutable $inicio, CarbonImmutable $fin): int
    {
        $dias = (int) $inicio->startOfDay()->diffInDays($fin->startOfDay()) + 1;

        return max(1, (int) ceil($dias / 7));
    }

    /**
     * @return array<int, array{CarbonImmutable, CarbonImmutable}> week number => first and last day
     */
    public static function rangos(CarbonImmutable $inicio, CarbonImmutable $fin): array
    {
        $rangos = [];

        foreach (range(1, self::contar($inicio, $fin)) as $semana) {
            $desde = $inicio->startOfDay()->addDays(7 * ($semana - 1));
            $rangos[$semana] = [$desde, $desde->addDays(6)->min($fin->startOfDay())];
        }

        return $rangos;
    }
}
