<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Enums;

// An acta is only open between an authorized rectification and its new validation.
enum EstadoActa: string
{
    case Abierta = 'abierta';
    case Cerrada = 'cerrada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Abierta => 'En rectificación',
            self::Cerrada => 'Cerrada',
        };
    }
}
