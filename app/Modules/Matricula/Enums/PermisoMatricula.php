<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum PermisoMatricula: string
{
    case Ver = 'matricula.ver';
    case Gestionar = 'matricula.gestionar';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
