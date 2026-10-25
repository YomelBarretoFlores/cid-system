<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Enums;

enum EstadoFinal: string
{
    case Aprobado = 'aprobado';
    case Desaprobado = 'desaprobado';
    case Nsp = 'nsp';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Aprobado => 'Aprobado',
            self::Desaprobado => 'Desaprobado',
            self::Nsp => 'NSP',
        };
    }
}
