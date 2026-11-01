<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Enums;

enum TipoDeColumna: string
{
    case Texto = 'texto';
    case Entero = 'entero';
    case Decimal = 'decimal';
    case Porcentaje = 'porcentaje';

    public function esNumero(): bool
    {
        return $this !== self::Texto;
    }

    public function formatoExcel(): ?string
    {
        return match ($this) {
            self::Decimal => '0.00',
            self::Entero, self::Porcentaje => '0',
            self::Texto => null,
        };
    }

    public function mostrar(string|int|float|null $valor): string
    {
        return match (true) {
            $valor === null => '—',
            $this === self::Decimal => number_format((float) $valor, 2, '.', ''),
            $this === self::Porcentaje => $valor.' %',
            default => (string) $valor,
        };
    }
}
