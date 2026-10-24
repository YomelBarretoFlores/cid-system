<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Enums;

enum EstadoPago: string
{
    case Pendiente = 'pendiente';
    case Validado = 'validado';
    case Rechazado = 'rechazado';
    case Anulado = 'anulado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Validado => 'Validado',
            self::Rechazado => 'Rechazado',
            self::Anulado => 'Anulado',
        };
    }
}
