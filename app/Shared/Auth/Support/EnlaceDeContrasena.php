<?php

declare(strict_types=1);

namespace App\Shared\Auth\Support;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Password;

// Staff never get a typed password: the reset link lets each person set their own.
final class EnlaceDeContrasena
{
    public static function enviar(string $email): void
    {
        $estado = Password::broker()->sendResetLink(['email' => $email]);

        throw_if($estado === Password::RESET_THROTTLED, ReglaDeNegocioIncumplida::class, 'Ya se envió un enlace a esta cuenta hace poco. Espera un minuto y vuelve a intentarlo.');
        throw_unless($estado === Password::RESET_LINK_SENT, ReglaDeNegocioIncumplida::class, 'No se pudo enviar el enlace para crear la contraseña.');
    }
}
