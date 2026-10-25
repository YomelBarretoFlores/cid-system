<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Enums;

enum CondicionDeEtapa: string
{
    case Solicitud = 'solicitud';
    case PagoValidado = 'pago_validado';
    case AccionDelPersonal = 'accion_del_personal';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Solicitud => 'Al recibir la solicitud',
            self::PagoValidado => 'Cuando Pagos valida la tasa',
            self::AccionDelPersonal => 'Cuando el personal la avanza',
        };
    }
}
