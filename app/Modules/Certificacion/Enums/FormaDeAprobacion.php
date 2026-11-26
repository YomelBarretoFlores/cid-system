<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Enums;

enum FormaDeAprobacion: string
{
    case Matricula = 'matricula';
    case Suficiencia = 'suficiencia';
    case Clasificacion = 'clasificacion';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Matricula => 'Matrícula',
            self::Suficiencia => 'Suficiencia',
            self::Clasificacion => 'Clasificación',
        };
    }
}
