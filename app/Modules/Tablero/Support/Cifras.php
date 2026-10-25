<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Pagos\Data\ReporteDeRecaudacion;

// The same wording for amounts, rates and hours on the page, the PDF and the spreadsheet.
final class Cifras
{
    public static function soles(int $centimos): string
    {
        return ($centimos < 0 ? '−' : '').'S/ '.ReporteDeRecaudacion::soles(abs($centimos));
    }

    public static function porcentaje(?float $valor): string
    {
        return $valor === null ? '—' : number_format($valor, 1).' %';
    }

    public static function variacion(?float $valor): string
    {
        return $valor === null ? '—' : ($valor >= 0 ? '+' : '−').number_format(abs($valor), 1).' %';
    }

    public static function horas(int $centesimas): string
    {
        return number_format($centesimas / 100, 2).' h';
    }
}
