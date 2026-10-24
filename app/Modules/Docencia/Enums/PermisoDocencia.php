<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum PermisoDocencia: string
{
    case Ver = 'docencia.ver';
    case Gestionar = 'docencia.gestionar';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
