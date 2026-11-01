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

    private const array UNIDADES = [
        'cero', 'uno', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince',
        'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve', 'veinte', 'veintiuno', 'veintidós', 'veintitrés', 'veinticuatro', 'veinticinco',
        'veintiséis', 'veintisiete', 'veintiocho', 'veintinueve',
    ];

    private const array DECENAS = [3 => 'treinta', 4 => 'cuarenta', 5 => 'cincuenta', 6 => 'sesenta', 7 => 'setenta', 8 => 'ochenta', 9 => 'noventa'];

    private const array CENTENAS = [
        1 => 'ciento', 2 => 'doscientos', 3 => 'trescientos', 4 => 'cuatrocientos', 5 => 'quinientos', 6 => 'seiscientos', 7 => 'setecientos',
        8 => 'ochocientos', 9 => 'novecientos',
    ];

    // A grade as the actas write it in words: "15.59" is "Quince con cincuenta y nueve"; the hundredths below ten keep their zero.
    public static function notaEnLetras(?string $nota): string
    {
        if ($nota === null || ! is_numeric($nota)) {
            return '—';
        }

        [$entero, $centesimas] = array_map(intval(...), explode('.', number_format((float) $nota, 2, '.', '')));
        $texto = self::numeroEnLetras($entero);

        if ($centesimas > 0) {
            $texto .= ' con '.($centesimas < 10 ? 'cero ' : '').self::numeroEnLetras($centesimas);
        }

        return mb_strtoupper(mb_substr($texto, 0, 1)).mb_substr($texto, 1);
    }

    // Whole numbers from 0 to 999 in Spanish words, feminine for things like hours ("doscientas cuarenta"); larger ones stay as digits.
    public static function numeroEnLetras(int $numero, bool $femenino = false): string
    {
        if ($numero < 0 || $numero > 999) {
            return (string) $numero;
        }

        $centena = intdiv($numero, 100);
        $resto = $numero % 100;
        $partes = array_filter([
            $centena === 0 ? null : ($numero === 100 ? 'cien' : self::CENTENAS[$centena] ?? ''),
            $resto === 0 && $numero > 0 ? null : self::decenas($resto),
        ]);
        $texto = implode(' ', $partes);

        return $femenino ? self::enFemenino($texto) : $texto;
    }

    private static function decenas(int $numero): string
    {
        if ($numero < 30) {
            return self::UNIDADES[$numero];
        }

        $unidad = $numero % 10;

        return self::DECENAS[intdiv($numero, 10)].($unidad === 0 ? '' : ' y '.self::UNIDADES[$unidad]);
    }

    private static function enFemenino(string $texto): string
    {
        return (string) preg_replace(['/ientos\b/', '/\buno$/', '/iuno$/'], ['ientas', 'una', 'iuna'], $texto);
    }
}
