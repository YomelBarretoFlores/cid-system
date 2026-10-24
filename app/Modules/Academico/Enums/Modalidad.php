<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum Modalidad: string
{
    case Presencial = 'presencial';
    case Virtual = 'virtual';
    case Semipresencial = 'semipresencial';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Presencial => 'Presencial',
            self::Virtual => 'Virtual',
            self::Semipresencial => 'Semipresencial',
        };
    }
}
