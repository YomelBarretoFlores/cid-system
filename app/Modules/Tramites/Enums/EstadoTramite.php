<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Enums;

enum EstadoTramite: string
{
    case PendientePago = 'pendiente_pago';
    case EnProceso = 'en_proceso';
    case Observado = 'observado';
    case Finalizado = 'finalizado';
    case Cerrado = 'cerrado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::PendientePago => 'Pendiente de pago',
            self::EnProceso => 'En proceso',
            self::Observado => 'Observado',
            self::Finalizado => 'Finalizado',
            self::Cerrado => 'Cerrado sin atender',
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
            self::Cerrado => 'danger',
        };
    }

    // Finished or closed without its final action: nothing changes afterwards and it no longer blocks a new request.
    public function terminado(): bool
    {
        return in_array($this, self::terminados(), true);
    }

    /**
     * @return list<self>
     */
    public static function terminados(): array
    {
        return [self::Finalizado, self::Cerrado];
    }
}
