<?php

declare(strict_types=1);

namespace App\Shared\Auth\Enums;

enum PermisoUsuario: string
{
    case Gestionar = 'usuarios.gestionar';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
