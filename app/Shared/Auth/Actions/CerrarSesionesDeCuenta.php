<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Enums\CierreDeSesiones;
use App\Shared\Auth\Support\CuentaPorCorreo;
use App\Shared\Support\Database\Transaccion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

// After a lost or stolen phone: "remember me" stops working at once and the open sessions end where they can be found by account.
final readonly class CerrarSesionesDeCuenta
{
    public function __invoke(string $email): CierreDeSesiones
    {
        $usuarioId = Transaccion::ejecutar(function () use ($email): int {
            $usuario = CuentaPorCorreo::bloquear($email);

            $usuario->forceFill(['remember_token' => Str::random(60)])->save();

            return $usuario->id;
        });

        return $this->borrarSesiones($usuarioId);
    }

    // Never throws: the caller has already committed its change, so a failure here is reported, not raised.
    public function borrarSesiones(int $usuarioId): CierreDeSesiones
    {
        if (config('session.driver') !== 'database') {
            Log::warning('No se pudieron cerrar las sesiones de una cuenta.', ['usuario_id' => $usuarioId, 'causa' => 'almacenamiento_sin_cuentas']);

            return CierreDeSesiones::SinAlmacenamientoPorCuenta;
        }

        try {
            DB::connection(config('session.connection'))->table(config()->string('session.table'))->where('user_id', $usuarioId)->delete();
        } catch (Throwable $error) {
            Log::warning('No se pudieron cerrar las sesiones de una cuenta.', ['usuario_id' => $usuarioId, 'causa' => $error::class]);

            return CierreDeSesiones::Fallido;
        }

        Log::info('Se cerraron las sesiones de una cuenta.', ['usuario_id' => $usuarioId]);

        return CierreDeSesiones::Cerradas;
    }
}
