<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Support;

// Crockford base32 without I, L, O and U: easy to read aloud and type from paper, and 10 characters are not guessable.
final class CodigoDeDocumento
{
    private const string ALFABETO = '0123456789ABCDEFGHJKMNPQRSTVWXYZ';

    private const int LARGO = 10;

    public static function nuevo(): string
    {
        $codigo = '';

        for ($posicion = 0; $posicion < self::LARGO; $posicion++) {
            $codigo .= self::ALFABETO[random_int(0, strlen(self::ALFABETO) - 1)];
        }

        return 'CILN-'.$codigo;
    }

    public static function normalizar(string $texto): string
    {
        $limpio = strtoupper((string) preg_replace('/[^0-9A-Za-z]/', '', $texto));

        return str_starts_with($limpio, 'CILN') ? 'CILN-'.substr($limpio, 4) : $limpio;
    }
}
