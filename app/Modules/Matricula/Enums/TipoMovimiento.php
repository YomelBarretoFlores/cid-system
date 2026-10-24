<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum TipoMovimiento: string
{
    case Traslado = 'traslado';
    case Retiro = 'retiro';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Traslado => 'Traslado',
            self::Retiro => 'Retiro',
        };
    }
}
