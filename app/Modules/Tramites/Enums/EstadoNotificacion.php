<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Enums;

enum EstadoNotificacion: string
{
    case Pendiente = 'pendiente';
    case Enviado = 'enviado';
    case Fallido = 'fallido';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pendiente => 'Pendiente',
            self::Enviado => 'Enviado',
            self::Fallido => 'Fallido',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Pendiente => 'gray',
            self::Enviado => 'success',
            self::Fallido => 'danger',
        };
    }
}
