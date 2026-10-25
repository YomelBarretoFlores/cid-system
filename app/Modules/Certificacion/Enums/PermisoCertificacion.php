<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Enums;

enum PermisoCertificacion: string
{
    case Actas = 'certificacion.actas';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
