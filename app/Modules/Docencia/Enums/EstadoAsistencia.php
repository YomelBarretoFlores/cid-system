<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum EstadoAsistencia: string
{
    case Presente = 'presente';
    case Falta = 'falta';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Presente => 'Presente',
            self::Falta => 'Ausente',
        };
    }

    // A student absent in the second call left during the class, so the class counts as an absence.
    public static function efectivo(self $inicio, ?self $cierre): self
    {
        return $cierre === self::Falta ? self::Falta : $inicio;
    }
}
