<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Enums;

enum FuenteConcepto: string
{
    case Tupa = 'tupa';
    case Tusne = 'tusne';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Tupa => 'TUPA',
            self::Tusne => 'TUSNE',
        };
    }
}
