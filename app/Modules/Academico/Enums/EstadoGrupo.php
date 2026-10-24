<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

enum EstadoGrupo: string
{
    case Programado = 'programado';
    case Abierto = 'abierto';
    case Cerrado = 'cerrado';
    case Cancelado = 'cancelado';
    case Finalizado = 'finalizado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Programado => 'Programado',
            self::Abierto => 'Abierto',
            self::Cerrado => 'Cerrado',
            self::Cancelado => 'Cancelado',
            self::Finalizado => 'Finalizado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Programado => 'info',
            self::Abierto => 'success',
            self::Cerrado => 'warning',
            self::Cancelado => 'danger',
            self::Finalizado => 'gray',
        };
    }

    public function esTerminal(): bool
    {
        return $this === self::Cancelado || $this === self::Finalizado;
    }

    public function puedeTransitarA(self $destino): bool
    {
        return in_array($destino, $this->destinosPermitidos(), true);
    }

    public function asegurarTransicionA(self $destino): void
    {
        if (! $this->puedeTransitarA($destino)) {
            throw new ReglaDeNegocioIncumplida("Un grupo {$this->etiqueta()} no puede pasar a {$destino->etiqueta()}.");
        }
    }

    /**
     * @return list<self>
     */
    private function destinosPermitidos(): array
    {
        return match ($this) {
            self::Programado => [self::Abierto, self::Cancelado],
            self::Abierto => [self::Cerrado, self::Cancelado],
            self::Cerrado => [self::Abierto, self::Finalizado, self::Cancelado],
            self::Cancelado, self::Finalizado => [],
        };
    }
}
