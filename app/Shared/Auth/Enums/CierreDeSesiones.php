<?php

declare(strict_types=1);

namespace App\Shared\Auth\Enums;

enum CierreDeSesiones
{
    case Cerradas;
    case SinAlmacenamientoPorCuenta;
    case Fallido;

    public function mensaje(): string
    {
        return match ($this) {
            self::Cerradas => 'Se cerraron las sesiones abiertas de la cuenta.',
            self::SinAlmacenamientoPorCuenta => 'Las sesiones no se guardan en la base de datos (SESSION_DRIVER), así que no se pueden cerrar por cuenta. Expirarán solas o al reiniciar el almacenamiento de sesiones.',
            self::Fallido => 'No se pudieron cerrar las sesiones abiertas de la cuenta. Cuando la conexión vuelva, ejecuta el comando con --solo-sesiones: cierra las sesiones sin volver a quitar el doble factor.',
        };
    }
}
