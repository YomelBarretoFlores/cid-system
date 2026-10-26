<?php

declare(strict_types=1);

namespace App\Shared\Ui;

use Carbon\CarbonInterface;

final class Formato
{
    private const array MESES = ['ene', 'feb', 'mar', 'abr', 'may', 'jun', 'jul', 'ago', 'set', 'oct', 'nov', 'dic'];

    private const array MESES_LARGOS = ['enero', 'febrero', 'marzo', 'abril', 'mayo', 'junio', 'julio', 'agosto', 'setiembre', 'octubre', 'noviembre', 'diciembre'];

    private const array ROMANOS = [1 => 'I', 'II', 'III', 'IV', 'V', 'VI', 'VII', 'VIII', 'IX', 'X', 'XI', 'XII'];

    // Public pages show only the last four characters of an identity document (Ley 29733): "DNI 41234567" becomes "DNI ••••4567".
    public static function documentoOculto(string $documento): string
    {
        if (preg_match('/^(.*?)(\S+)$/u', $documento, $partes) !== 1) {
            return '••••';
        }

        return $partes[1].str_repeat('•', max(0, mb_strlen($partes[2]) - 4)).mb_substr($partes[2], -4);
    }

    public static function fechaCorta(CarbonInterface $fecha): string
    {
        return $fecha->day.' '.self::MESES[$fecha->month - 1].' '.$fecha->year;
    }

    public static function diaYMes(CarbonInterface $fecha): string
    {
        return $fecha->day.' '.self::MESES[$fecha->month - 1];
    }

    public static function diaDeMes(CarbonInterface $fecha): string
    {
        return $fecha->day.' de '.self::MESES_LARGOS[$fecha->month - 1];
    }

    public static function fechaLarga(CarbonInterface $fecha): string
    {
        return self::diaDeMes($fecha).' de '.$fecha->year;
    }

    // "6 – 31 oct" within one month, "28 set – 5 oct" across two; a single day is "3 nov".
    public static function rango(CarbonInterface $desde, CarbonInterface $hasta): string
    {
        if ($desde->isSameDay($hasta)) {
            return self::diaYMes($desde);
        }

        $inicio = $desde->isSameMonth($hasta) ? (string) $desde->day : self::diaYMes($desde);

        return $inicio.' – '.self::diaYMes($hasta);
    }

    public static function romano(int $numero): string
    {
        return self::ROMANOS[$numero] ?? (string) $numero;
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
