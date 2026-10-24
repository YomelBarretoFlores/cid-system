<?php

declare(strict_types=1);

namespace App\Modules\Personas\Enums;

enum PermisoPersona: string
{
    case Ver = 'personas.ver';
    case Gestionar = 'personas.gestionar';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
