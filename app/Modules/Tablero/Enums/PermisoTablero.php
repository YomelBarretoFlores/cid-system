<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Enums;

enum PermisoTablero: string
{
    case Ver = 'tablero.ver';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
