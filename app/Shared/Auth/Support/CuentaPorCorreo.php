<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final class CuentaPorCorreo
{
    // Call inside a transaction: the account row stays locked until it ends.
    public static function bloquear(string $email): User
    {
        return User::query()->where('email', mb_strtolower(trim($email)))->lockForUpdate()->first()
            ?? throw new ReglaDeNegocioIncumplida('No hay una cuenta con ese correo.');
    }
}
