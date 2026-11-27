<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum TipoDeComponente: string
{
    case Ingresada = 'ingresada';
    case Calculada = 'calculada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Ingresada => 'La ingresa el docente',
            self::Calculada => 'Calculada',
        };
    }
}
