<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Enums;

enum PermisoPago: string
{
    case Ver = 'pagos.ver';
    case Registrar = 'pagos.registrar';
    case Validar = 'pagos.validar';
    case Anular = 'pagos.anular';
    case Configurar = 'pagos.configurar';

    /**
     * @return list<string>
     */
    public static function valores(): array
    {
        return array_column(self::cases(), 'value');
    }
}
