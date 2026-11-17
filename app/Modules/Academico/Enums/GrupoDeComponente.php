<?php

declare(strict_types=1);

namespace App\Modules\Academico\Enums;

enum GrupoDeComponente: string
{
    case Habilidades = 'habilidades';
    case Examenes = 'examenes';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Habilidades => 'Habilidades',
            self::Examenes => 'Exámenes',
        };
    }

    public static function delCodigo(string $codigo): self
    {
        return in_array($codigo, ['parcial', 'final'], true) ? self::Examenes : self::Habilidades;
    }
}
