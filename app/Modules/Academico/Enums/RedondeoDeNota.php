<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum RedondeoDeNota: string
{
    case Ninguno = 'ninguno';
    case Entero = 'entero';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Ninguno => 'Sin redondear (dos decimales)',
            self::Entero => 'Al entero (0.5 hacia arriba)',
        };
    }
}
