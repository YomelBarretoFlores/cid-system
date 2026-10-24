<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum EstadoAsistencia: string
{
    case Presente = 'presente';
    case Tardanza = 'tardanza';
    case Falta = 'falta';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::Tardanza => 'Tardanza',
            self::Falta => 'Falta',
        };
    }
}
