<?php

declare(strict_types=1);

namespace App\Shared\Auth\Enums;

enum TemaDeInterfaz: string
{
    case Claro = 'claro';
    case Oscuro = 'oscuro';
    case Sistema = 'sistema';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Claro => 'Claro',
            self::Oscuro => 'Oscuro',
            self::Sistema => 'Del sistema',
        };
    }
}
