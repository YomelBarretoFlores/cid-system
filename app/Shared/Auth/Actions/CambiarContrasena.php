<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Notifications\AvisoDeCambioDeContrasena;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

// A new remember token ends "remember me" on every other device; the caller keeps its own session.
final readonly class CambiarContrasena
{
    public const string ACTUAL_INCORRECTA = 'Tu contraseña actual no es correcta.';

    public function __invoke(int $usuarioId, #[\SensitiveParameter] string $actual, #[\SensitiveParameter] string $nueva): User
    {
        $usuario = Transaccion::ejecutar(function () use ($usuarioId, $actual, $nueva): User {
            $usuario = User::query()->whereKey($usuarioId)->lockForUpdate()->firstOrFail();

            if (! Hash::check($actual, $usuario->password)) {
                throw new ReglaDeNegocioIncumplida(self::ACTUAL_INCORRECTA);
            }

            $usuario->forceFill(['password' => $nueva, 'remember_token' => Str::random(60)])->save();

            return $usuario;
        });

        $usuario->notify(new AvisoDeCambioDeContrasena);

        return $usuario;
    }
}
