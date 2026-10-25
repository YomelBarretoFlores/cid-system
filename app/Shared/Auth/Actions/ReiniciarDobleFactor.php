<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Enums\CierreDeSesiones;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// For staff who lost both the phone and the recovery codes; the panel asks them to set it up again.
final readonly class ReiniciarDobleFactor
{
    public function __construct(private CerrarSesionesDeCuenta $cerrarSesiones) {}

    public function __invoke(string $email): CierreDeSesiones
    {
        $usuarioId = Transaccion::ejecutar(function () use ($email): int {
            $usuario = User::query()->where('email', mb_strtolower(trim($email)))->lockForUpdate()->first()
                ?? throw new ReglaDeNegocioIncumplida('No hay una cuenta con ese correo.');

            // The remember token rotates with the reset, so a stolen phone's "remember me" never outlives it.
            $usuario->forceFill([
                'app_authentication_secret' => null,
                'app_authentication_recovery_codes' => null,
                'remember_token' => Str::random(60),
            ])->save();

            return $usuario->id;
        });

        Log::info('Se reinició el doble factor de una cuenta.', ['usuario_id' => $usuarioId]);

        // After the commit, so a failure here never undoes the reset and --solo-sesiones can retry just this part.
        return $this->cerrarSesiones->borrarSesiones($usuarioId);
    }
}
