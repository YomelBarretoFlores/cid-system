<?php

declare(strict_types=1);

namespace App\Modules\Personas\Support;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use GdImage;

// Re-encodes a profile photo so no metadata (GPS, device) survives (Ley 29733), upright and at most LADO_MAXIMO pixels.
final class FotoNormalizada
{
    public const int LADO_MAXIMO = 800;

    private const int ORIENTACION = 0x0112;

    public static function desde(string $ruta, string $tipo): string
    {
        $bytes = (string) file_get_contents($ruta);
        $imagen = @imagecreatefromstring($bytes);

        if (! $imagen instanceof GdImage) {
            throw new ReglaDeNegocioIncumplida('No pudimos leer tu fotografía. Prueba con otra imagen JPG o PNG.');
        }

        if ($tipo === 'image/jpeg') {
            $imagen = self::enderezar($imagen, self::orientacion($bytes));
        }

        return self::codificar(self::reducir($imagen), $tipo);
    }

    private static function enderezar(GdImage $imagen, int $orientacion): GdImage
    {
        $girada = match ($orientacion) {
            3 => imagerotate($imagen, 180, 0),
            5, 6 => imagerotate($imagen, 270, 0),
            7, 8 => imagerotate($imagen, 90, 0),
            default => $imagen,
        };
        $girada = $girada === false ? $imagen : $girada;

        if (in_array($orientacion, [2, 5, 7], true)) {
            imageflip($girada, IMG_FLIP_HORIZONTAL);
        }

        if ($orientacion === 4) {
            imageflip($girada, IMG_FLIP_VERTICAL);
        }

        return $girada;
    }

    private static function reducir(GdImage $imagen): GdImage
    {
        $ancho = imagesx($imagen);
        $alto = imagesy($imagen);
        $escala = self::LADO_MAXIMO / max($ancho, $alto);

        if ($escala >= 1) {
            return $imagen;
        }

        $reducida = imagescale($imagen, max(1, (int) round($ancho * $escala)), max(1, (int) round($alto * $escala)), IMG_BILINEAR_FIXED);

        return $reducida === false ? $imagen : $reducida;
    }

    private static function codificar(GdImage $imagen, string $tipo): string
    {
        ob_start();

        if ($tipo === 'image/png') {
            imagealphablending($imagen, false);
            imagesavealpha($imagen, true);
            imagepng($imagen, null, 6);
        } else {
            imagejpeg($imagen, null, 85);
        }

        return (string) ob_get_clean();
    }

    // Reads the EXIF orientation tag straight from the JPEG segments: the exif extension is not installed in production.
    private static function orientacion(string $jpeg): int
    {
        $posicion = 2;
        $largo = strlen($jpeg);

        while ($posicion + 4 <= $largo && $jpeg[$posicion] === "\xFF") {
            $marcador = ord($jpeg[$posicion + 1]);
            $tamano = self::numero('n', $jpeg, $posicion + 2, 2);

            if ($marcador === 0xDA) {
                return 1;
            }

            if ($marcador === 0xE1 && substr($jpeg, $posicion + 4, 6) === "Exif\0\0") {
                return self::orientacionTiff(substr($jpeg, $posicion + 10, $tamano - 8));
            }

            $posicion += 2 + $tamano;
        }

        return 1;
    }

    private static function orientacionTiff(string $tiff): int
    {
        $corto = str_starts_with($tiff, 'MM') ? 'n' : 'v';
        $largo = $corto === 'n' ? 'N' : 'V';
        $ifd = self::numero($largo, $tiff, 4, 4);
        $entradas = self::numero($corto, $tiff, $ifd, 2);

        for ($i = 0; $i < $entradas; $i++) {
            $entrada = $ifd + 2 + $i * 12;

            if (self::numero($corto, $tiff, $entrada, 2) === self::ORIENTACION) {
                return self::numero($corto, $tiff, $entrada + 8, 2) ?: 1;
            }
        }

        return 1;
    }

    // A truncated or malformed block reads as 0 instead of failing the upload.
    private static function numero(string $formato, string $bytes, int $posicion, int $tamano): int
    {
        $trozo = substr($bytes, $posicion, $tamano);

        if ($posicion < 0 || strlen($trozo) < $tamano) {
            return 0;
        }

        return (int) (unpack($formato, $trozo)[1] ?? 0);
    }
}
