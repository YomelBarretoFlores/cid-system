<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum PermisoClasificacion: string
{
    case Ver = 'clasificacion.ver';
    case Evaluar = 'clasificacion.evaluar';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
