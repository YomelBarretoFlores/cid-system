<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

// The sidebar shows how many records wait for staff; nothing pending shows no badge.
final class ContadorDePendientes
{
    /**
     * @param  Builder<covariant Model>  $pendientes
     */
    public static function de(Builder $pendientes): ?string
    {
        $cantidad = $pendientes->count();

        return match (true) {
            $cantidad === 0 => null,
            $cantidad > 99 => '99+',
            default => (string) $cantidad,
        };
    }
}
