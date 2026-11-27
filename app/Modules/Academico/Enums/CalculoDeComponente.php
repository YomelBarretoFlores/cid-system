<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum CalculoDeComponente: string
{
    case Promedio = 'promedio';
    case Ponderado = 'ponderado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Promedio => 'Promedio simple',
            self::Ponderado => 'Promedio ponderado',
        };
    }
}
