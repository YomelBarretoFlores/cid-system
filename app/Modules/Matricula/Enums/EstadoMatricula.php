<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum EstadoMatricula: string
{
    case Activa = 'activa';
    case Retirada = 'retirada';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activa => 'Activa',
            self::Retirada => 'Retirada',
        };
    }
}
