<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final class TemaDeLaClase
{
    private const int LIMITE = 1000;

    public static function normalizar(?string $tema): ?string
    {
        $limpio = trim((string) preg_replace('/\s+/u', ' ', (string) $tema));

        if (mb_strlen($limpio) > self::LIMITE) {
            throw new ReglaDeNegocioIncumplida('El tema de la clase no puede pasar de '.self::LIMITE.' caracteres.');
        }

        return $limpio === '' ? null : $limpio;
    }

    public static function exigir(?string $tema): string
    {
        return $tema ?? throw new ReglaDeNegocioIncumplida('Escribe el tema que dictaste en esta clase.');
    }
}
