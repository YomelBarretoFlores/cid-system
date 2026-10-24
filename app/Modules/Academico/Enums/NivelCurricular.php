<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum NivelCurricular: string
{
    case Basico = 'basico';
    case Intermedio = 'intermedio';
    case Avanzado = 'avanzado';

    public function orden(): int
    {
        return match ($this) {
            self::Basico => 1,
            self::Intermedio => 2,
            self::Avanzado => 3,
        };
    }

    public function etiqueta(): string
    {
        return match ($this) {
            self::Basico => 'Básico',
            self::Intermedio => 'Intermedio',
            self::Avanzado => 'Avanzado',
        };
    }
}
