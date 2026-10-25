<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// For staff who lost both the phone and the recovery codes; the panel asks them to set it up again.
final readonly class ReiniciarDobleFactor
{
    // Returns whether the account's open sessions were closed too.
    public function __invoke(string $email): bool
    {
        $usuarioId = Transaccion::ejecutar(function () use ($email): int {
            $usuario = User::query()->where('email', mb_strtolower(trim($email)))->lockForUpdate()->first()
                ?? throw new ReglaDeNegocioIncumplida('No hay una cuenta con ese correo.');

            $usuario->forceFill([
                'app_authentication_secret' => null,
                'app_authentication_recovery_codes' => null,
                'remember_token' => Str::random(60),
            ])->save();

            return $usuario->id;
        });

        $sesionesCerradas = $this->cerrarSesiones($usuarioId);
        Log::info('Se reinició el doble factor de una cuenta.', ['usuario_id' => $usuarioId, 'sesiones_cerradas' => $sesionesCerradas]);

        return $sesionesCerradas;
    }

    // The reset may follow a stolen phone, so the account's open sessions end too; only database sessions can be found by account.
    private function cerrarSesiones(int $usuarioId): bool
    {
        if (config('session.driver') !== 'database') {
            return false;
        }

        DB::connection(config('session.connection'))->table(config()->string('session.table'))->where('user_id', $usuarioId)->delete();

        return true;
    }
}
