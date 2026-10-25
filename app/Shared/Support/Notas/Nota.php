<?php

declare(strict_types=1);

namespace App\Shared\Support\Notas;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// Grades travel as decimal strings and are computed in integer hundredths, never as floats.
final class Nota
{
    public static function leer(mixed $valor, string $maxima): ?string
    {
        $limite = rtrim(rtrim($maxima, '0'), '.');

        if (! is_string($valor) && ! is_int($valor) && ! is_float($valor) && $valor !== null) {
            throw new ReglaDeNegocioIncumplida("Una de las notas no es válida: usa un número de 0 a {$limite} con hasta dos decimales.");
        }

        $texto = str_replace(',', '.', trim((string) $valor));

        if ($texto === '') {
            return null;
        }

        if (preg_match('/^\d{1,2}(\.\d{1,2})?\z/', $texto) !== 1 || self::centesimos($texto) > self::centesimos($maxima)) {
            throw new ReglaDeNegocioIncumplida("La nota {$texto} no es válida: usa un número de 0 a {$limite} con hasta dos decimales.");
        }

        return self::deCentesimos(self::centesimos($texto));
    }

    public static function centesimos(string $numero): int
    {
        [$entero, $decimales] = explode('.', $numero.'.');

        return (int) $entero * 100 + (int) str_pad(substr($decimales, 0, 2), 2, '0');
    }

    public static function deCentesimos(int $centesimos): string
    {
        return sprintf('%d.%02d', intdiv($centesimos, 100), $centesimos % 100);
    }
}
