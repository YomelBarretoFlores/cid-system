<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Enums;

enum EstadoTramite: string
{
    case PendientePago = 'pendiente_pago';
    case EnProceso = 'en_proceso';
    case Observado = 'observado';
    case Finalizado = 'finalizado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PendientePago => 'Pendiente de pago',
            self::EnProceso => 'En proceso',
            self::Observado => 'Observado',
            self::Finalizado => 'Finalizado',
        };
    }

    // Shared by the panel badges and the portal's x-portal.insignia.
    public function color(): string
    {
        return match ($this) {
            self::PendientePago => 'gray',
            self::EnProceso => 'info',
            self::Observado => 'warning',
            self::Finalizado => 'success',
        };
    }
}
