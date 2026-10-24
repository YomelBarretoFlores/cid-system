<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum EstadoDocente: string
{
    case Activo = 'activo';
    case Inactivo = 'inactivo';
    case DeLicencia = 'licencia';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Activo => 'Activo',
            self::Inactivo => 'Inactivo',
            self::DeLicencia => 'De licencia',
        };
    }

    public function color(): string
    {
        return match ($this) {
            self::Activo => 'success',
            self::Inactivo => 'gray',
            self::DeLicencia => 'warning',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return array_column(array_map(fn (self $estado): array => [$estado->value, $estado->etiqueta()], self::cases()), 1, 0);
    }
}
