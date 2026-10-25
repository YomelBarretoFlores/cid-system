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
}
