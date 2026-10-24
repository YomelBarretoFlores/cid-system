<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum PermisoAcademico: string
{
    case Ver = 'academico.ver';
    case Gestionar = 'academico.gestionar';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
