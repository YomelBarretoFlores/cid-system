<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum EstadoSilabo: string
{
    case Borrador = 'borrador';
    case Publicado = 'publicado';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Borrador => 'Borrador',
            self::Publicado => 'Publicado',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Borrador => 'warning',
            self::Publicado => 'success',
        };
    }

    // A published syllabus can be corrected but never goes back to draft.
    public function puedeTransitarA(self $destino): bool
    {
        return $this === self::Borrador || $destino === self::Publicado;
    }

    public static function etiquetaDe(?self $estado): string
    {
        return $estado?->etiqueta() ?? 'Falta';
    }

    public static function colorDe(?self $estado): string
    {
        return $estado?->color() ?? 'danger';
    }
}
