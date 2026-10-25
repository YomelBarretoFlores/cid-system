<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Enums;

enum PermisoTramite: string
{
    case Gestionar = 'tramites.gestionar';
    case Configurar = 'tramites.configurar';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
