<?php

declare(strict_types=1);

namespace App\Shared\Audit\Enums;

enum PermisoAuditoria: string
{
    case Ver = 'auditoria.ver';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
