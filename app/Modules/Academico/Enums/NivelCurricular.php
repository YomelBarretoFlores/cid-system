<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum NivelCurricular: string
{
    case Basico = 'basico';
    case Intermedio = 'intermedio';
    case Avanzado = 'avanzado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Basico => 'Básico',
            self::Intermedio => 'Intermedio',
            self::Avanzado => 'Avanzado',
        };
    }
}
