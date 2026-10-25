<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Enums;

enum PermisoCertificacion: string
{
    case Actas = 'certificacion.actas';
    case Documentos = 'certificacion.documentos';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
