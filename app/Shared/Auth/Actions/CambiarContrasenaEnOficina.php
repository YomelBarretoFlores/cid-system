<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// A teacher who forgot the password gets a new one at the office; the caller authorizes the staff member and validates it.
final readonly class CambiarContrasenaEnOficina
{
    public function __construct(private CerrarSesionesDeCuenta $cerrarSesiones) {}

    public function __invoke(int $usuarioId, string $contrasena, User $actor): void
    {
        Transaccion::ejecutar(function () use ($usuarioId, $contrasena): void {
            $usuario = User::query()->whereKey($usuarioId)->lockForUpdate()->firstOrFail();
            $usuario->forceFill(['password' => $contrasena, 'remember_token' => Str::random(60)])->save();
            $usuario->tokens()->delete();
        });

        Log::info('Se cambió en la oficina la contraseña de una cuenta.', ['usuario_id' => $usuarioId, 'cambiado_por' => $actor->id]);

        // After the commit, so a failure closing sessions never undoes the change.
        $this->cerrarSesiones->borrarSesiones($usuarioId);
    }
}
