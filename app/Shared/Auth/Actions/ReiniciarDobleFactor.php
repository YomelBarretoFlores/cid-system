<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Log;

// For staff who lost both the phone and the recovery codes; the panel asks them to set it up again.
final readonly class ReiniciarDobleFactor
{
    public function __invoke(string $email): void
    {
        $usuarioId = Transaccion::ejecutar(function () use ($email): int {
            $usuario = User::query()->where('email', mb_strtolower(trim($email)))->lockForUpdate()->first()
                ?? throw new ReglaDeNegocioIncumplida('No hay una cuenta con ese correo.');

            $usuario->forceFill(['app_authentication_secret' => null, 'app_authentication_recovery_codes' => null])->save();

            return $usuario->id;
        });

        Log::info('Se reinició el doble factor de una cuenta.', ['usuario_id' => $usuarioId]);
    }
}
