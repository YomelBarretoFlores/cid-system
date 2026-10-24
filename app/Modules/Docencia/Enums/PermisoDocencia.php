<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum PermisoDocencia: string
{
    case Ver = 'docencia.ver';
    case Gestionar = 'docencia.gestionar';
    case Costos = 'docencia.costos';
    case Reportar = 'docencia.reportar';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
