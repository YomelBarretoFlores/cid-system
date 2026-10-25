<?php

declare(strict_types=1);

namespace App\Shared\Ui;

use Carbon\CarbonInterface;

final class Formato
{
    private const array MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'set', 'oct', 'nov', 'dic'];

    public static function fechaCorta(CarbonInterface $fecha): string
    {
        return $fecha->day.' '.self::MESES[$fecha->month - 1].' '.$fecha->year;
    }

    // Grades and weights without trailing zeros (15.50 → 15.5, 40.00 → 40); a missing grade is a dash.
    public static function nota(?string $decimal): string
    {
        if ($decimal === null) {
            return '—';
        }

        return str_contains($decimal, '.') ? rtrim(rtrim($decimal, '0'), '.') : $decimal;
    }
}
