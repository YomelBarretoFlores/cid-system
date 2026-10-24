<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Enums;

enum CondicionLaboral: string
{
    case Nombrado = 'nombrado';
    case Contratado = 'contratado';
    case Tercero = 'tercero';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Nombrado => 'Nombrado',
            self::Contratado => 'Contratado',
            self::Tercero => 'Tercero',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return array_column(array_map(fn (self $condicion): array => [$condicion->value, $condicion->etiqueta()], self::cases()), 1, 0);
    }
}
