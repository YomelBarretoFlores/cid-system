<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Exceptions\CredencialesIncorrectas;
use App\Shared\Auth\Models\User;
use Illuminate\Auth\Events\Attempting;
use Illuminate\Auth\Events\Failed;
use Illuminate\Auth\Events\Validated;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

// Checks the credentials under the sign-in limits shared by the web portal and the API; it never opens a session.
final readonly class AutenticarUsuario
{
    public const string CREDENCIALES_INCORRECTAS = 'El usuario o la contraseña no son correctos.';

    private const int INTENTOS_POR_MINUTO = 5;

    private const int INTENTOS_POR_DIRECCION = 20;

    // The identifier is an email when it has an @, otherwise the username staff gave the account (the DNI of a teacher).
    public function __invoke(string $identificador, string $password, string $direccion): User
    {
        $campo = str_contains($identificador, '@') ? 'email' : 'usuario';
        $identificador = (string) ($campo === 'email' ? User::normalizarCorreo($identificador) : User::normalizarUsuario($identificador));
        $clave = "ingreso|{$identificador}|{$direccion}";
        // The per-address key also stops one address from trying a common password on many accounts.
        $claveDeDireccion = "ingreso|{$direccion}";

        $this->asegurarIntentosDisponibles($clave, self::INTENTOS_POR_MINUTO);
        $this->asegurarIntentosDisponibles($claveDeDireccion, self::INTENTOS_POR_DIRECCION);

        // The same steps as Auth::attempt without opening a session: Attempting, Failed or Validated, and the hash upgrade on success.
        $proveedor = Auth::guard('web')->getProvider();
        $credenciales = [$campo => $identificador, 'password' => $password];
        event(new Attempting('web', $credenciales, false));
        $usuario = $proveedor->retrieveByCredentials($credenciales);

        // A deactivated account gets the same answer as a wrong password, so the message never reveals that it exists.
        if (! $usuario instanceof User || ! $proveedor->validateCredentials($usuario, $credenciales) || $usuario->desactivado_en !== null) {
            event(new Failed('web', $usuario, $credenciales));
            RateLimiter::hit($clave);
            RateLimiter::hit($claveDeDireccion);

            throw CredencialesIncorrectas::withMessages(['email' => self::CREDENCIALES_INCORRECTAS]);
        }

        event(new Validated('web', $usuario));
        RateLimiter::clear($clave);
        $proveedor->rehashPasswordIfRequired($usuario, $credenciales);

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
