<?php

declare(strict_types=1);

namespace App\Shared\Ui;

use InvalidArgumentException;

// Code 128 B drawn as a PNG, for printed records that an office scanner reads; DOMPDF draws embedded PNG images reliably.
final class CodigoDeBarras
{
    // Bar and space widths of each symbol value 0–105, from the Code 128 specification.
    private const array PATRONES = [
        '212222', '222122', '222221', '121223', '121322', '131222', '122213', '122312', '132212', '221213',
        '221312', '231212', '112232', '122132', '122231', '113222', '123122', '123221', '223211', '221132',
        '221231', '213212', '223112', '312131', '311222', '321122', '321221', '312212', '322112', '322211',
        '212123', '212321', '232121', '111323', '131123', '131321', '112313', '132113', '132311', '211313',
        '231113', '231311', '112133', '112331', '132131', '113123', '113321', '133121', '313121', '211331',
        '231131', '213113', '213311', '213131', '311123', '311321', '331121', '312113', '312311', '332111',
        '314111', '221411', '431111', '111224', '111422', '121124', '121421', '141122', '141221', '112214',
        '112412', '122114', '122411', '142112', '142211', '241211', '221114', '413111', '241112', '134111',
        '111242', '121142', '121241', '114212', '124112', '124211', '411212', '421112', '421211', '212141',
        '214121', '412121', '111143', '111341', '131141', '114113', '114311', '411113', '411311', '113141',
        '114131', '311141', '411131', '211412', '211214', '211232',
    ];

    private const string PARADA = '2331112';

    private const int INICIO_B = 104;

    private const int ZONA_SILENCIOSA = 10;

    public static function modulos(string $texto): string
    {
        $valores = array_map(self::valor(...), str_split($texto));
        $control = array_reduce(array_keys($valores), fn (int $suma, int $posicion): int => $suma + $valores[$posicion] * ($posicion + 1), self::INICIO_B) % 103;

        return implode('', array_map(self::barras(...), [...array_map(fn (int $valor): string => self::PATRONES[$valor], [self::INICIO_B, ...$valores, $control]), self::PARADA]));
    }

    public static function comoDataUri(string $texto, int $anchoDeModulo = 2, int $alto = 60): string
    {
        $modulos = str_repeat('0', self::ZONA_SILENCIOSA).self::modulos($texto).str_repeat('0', self::ZONA_SILENCIOSA);
        $anchoDeModulo = max(1, $anchoDeModulo);
        $alto = max(1, $alto);
        $imagen = imagecreate(max(1, strlen($modulos) * $anchoDeModulo), $alto);
        imagecolorallocate($imagen, 255, 255, 255);
        $negro = (int) imagecolorallocate($imagen, 0, 0, 0);

        foreach (str_split($modulos) as $posicion => $modulo) {
            if ($modulo === '1') {
                imagefilledrectangle($imagen, $posicion * $anchoDeModulo, 0, ($posicion + 1) * $anchoDeModulo - 1, $alto - 1, $negro);
            }
        }

        ob_start();
        imagepng($imagen);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }

    private static function valor(string $caracter): int
    {
        $codigo = ord($caracter);

        return $codigo >= 32 && $codigo <= 126
            ? $codigo - 32
            : throw new InvalidArgumentException('Code 128 B only encodes printable ASCII characters.');
    }

    // Widths alternate bar and space, starting with a bar: "212222" becomes 11 0 11 00 11 00.
    private static function barras(string $patron): string
    {
        return implode('', array_map(fn (string $ancho, int $posicion): string => str_repeat($posicion % 2 === 0 ? '1' : '0', (int) $ancho), str_split($patron), array_keys(str_split($patron))));
    }
}
