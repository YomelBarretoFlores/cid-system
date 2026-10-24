<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Enums;

enum CondicionMatricula: string
{
    case Regular = 'regular';
    case Repitente = 'repitente';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Regular => 'Regular',
            self::Repitente => 'Repitente',
        };
    }
}
