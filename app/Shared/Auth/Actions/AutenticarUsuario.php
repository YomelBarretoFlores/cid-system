<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

// Checks the credentials under the sign-in limits shared by the web portal and the API; it never opens a session.
final readonly class AutenticarUsuario
{
    public const string CREDENCIALES_INCORRECTAS = 'El correo o la contraseña no son correctos.';

    private const int INTENTOS_POR_MINUTO = 5;

    private const int INTENTOS_POR_DIRECCION = 20;

    public function __invoke(string $email, string $password, string $direccion): User
    {
        $email = mb_strtolower(trim($email));
        $clave = "ingreso|{$email}|{$direccion}";
        // The per-address key also stops one address from trying a common password on many accounts.
        $claveDeDireccion = "ingreso|{$direccion}";

        $this->asegurarIntentosDisponibles($clave, self::INTENTOS_POR_MINUTO);
        $this->asegurarIntentosDisponibles($claveDeDireccion, self::INTENTOS_POR_DIRECCION);

        $guard = Auth::guard('web');
        $usuario = $guard->validate(['email' => $email, 'password' => $password]) ? $guard->getLastAttempted() : null;

        if (! $usuario instanceof User) {
            RateLimiter::hit($clave);
            RateLimiter::hit($claveDeDireccion);

            throw ValidationException::withMessages(['email' => self::CREDENCIALES_INCORRECTAS]);
        }

        RateLimiter::clear($clave);

        return $usuario;
    }

    private function asegurarIntentosDisponibles(string $clave, int $maximo): void
    {
        if (RateLimiter::tooManyAttempts($clave, $maximo)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Vuelve a intentarlo en '.RateLimiter::availableIn($clave).' segundos.',
            ]);
        }
    }
}
