<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final class CuentaSoloDelPortal
{
    // Staff accounts keep their own flow (second factor, last administrator), so a teaching manager can never take one over.
    public static function bloquear(int $usuarioId): User
    {
        $usuario = User::query()->whereKey($usuarioId)->lockForUpdate()->firstOrFail();

        throw_if(
            $usuario->roles()->exists() || $usuario->permissions()->exists(),
            ReglaDeNegocioIncumplida::class,
            'Es una cuenta del personal: gestiónala desde Usuarios del personal.',
        );

        return $usuario;
    }
}
