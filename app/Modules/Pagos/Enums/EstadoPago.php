<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Enums;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

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

    public function puedeTransitarA(self $destino): bool
    {
        return in_array($destino, $this->destinosPermitidos(), true);
    }

    public function asegurarTransicionA(self $destino): void
    {
        if (! $this->puedeTransitarA($destino)) {
            throw new ReglaDeNegocioIncumplida("Un pago {$this->etiqueta()} no puede pasar a {$destino->etiqueta()}.");
        }
    }

    /**
     * @return list<self>
     */
    private function destinosPermitidos(): array
    {
        return match ($this) {
            self::Pendiente => [self::Validado, self::Rechazado],
            self::Validado => [self::Anulado],
            self::Rechazado, self::Anulado => [],
        };
    }
}
