<?php

declare(strict_types=1);

namespace App\Modules\Personas\Enums;

enum TipoDocumento: string
{
    case Dni = 'dni';
    case Ce = 'ce';
    case Pasaporte = 'pasaporte';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Dni => 'DNI',
            self::Ce => 'Carné de Extranjería',
            self::Pasaporte => 'Pasaporte',
        };
    }
}
