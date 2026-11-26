<?php

declare(strict_types=1);

namespace App\Shared\Ui;

// The letterhead image holds the shield beside the university name; documents that print the name as text take only the shield.
final class EscudoUnasam
{
    private const int ANCHO_DEL_ESCUDO = 176;

    public static function comoDataUri(): string
    {
        $png = (string) file_get_contents(resource_path('images/pdf/unasam.png'));
        $imagen = function_exists('imagecreatefromstring') ? imagecreatefromstring($png) : false;
        $escudo = $imagen === false ? false : imagecrop($imagen, ['x' => 0, 'y' => 0, 'width' => self::ANCHO_DEL_ESCUDO, 'height' => imagesy($imagen)]);

        if ($escudo === false) {
            return 'data:image/png;base64,'.base64_encode($png);
        }

        imagesavealpha($escudo, true);
        ob_start();
        imagepng($escudo);

        return 'data:image/png;base64,'.base64_encode((string) ob_get_clean());
    }
}
