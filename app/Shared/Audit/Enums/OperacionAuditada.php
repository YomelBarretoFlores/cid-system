<?php

declare(strict_types=1);

namespace App\Shared\Audit\Enums;

enum OperacionAuditada: string
{
    case Alta = 'INSERT';
    case Cambio = 'UPDATE';
    case Baja = 'DELETE';

    public function etiqueta(): string
    {
        return $this->name;
    }

    /**
     * @return array<string, string>
     */
    public static function opciones(): array
    {
        return array_column(array_map(fn (self $operacion): array => [$operacion->value, $operacion->etiqueta()], self::cases()), 1, 0);
    }
}
