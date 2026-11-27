<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\CuentaSoloDelPortal;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// For a teacher who left CILN, a compromised or misused account, or a duplicate; never for inactivity, since a
// former student may still need a certificate. Nothing is deleted, and the caller authorizes the staff member.
final readonly class DesactivarCuentaDelPortal
{
    public function __construct(private CerrarSesionesDeCuenta $cerrarSesiones) {}

    public function __invoke(int $usuarioId, string $motivo, User $actor): void
    {
        $motivo = trim($motivo);
        throw_if($motivo === '' || mb_strlen($motivo) > DesactivarUsuario::MAXIMO_DEL_MOTIVO, ReglaDeNegocioIncumplida::class, 'Escribe el motivo de la baja, de hasta 500 caracteres.');

        $desactivada = Transaccion::ejecutar(function () use ($usuarioId): bool {
            $usuario = CuentaSoloDelPortal::bloquear($usuarioId);

            if ($usuario->desactivado_en !== null) {
                return false;
            }

            $usuario->forceFill(['desactivado_en' => now(), 'remember_token' => Str::random(60)])->save();
            $usuario->tokens()->delete();

            return true;
        });

        if (! $desactivada) {
            return;
        }

        Log::info('Se dio de baja una cuenta del portal.', ['usuario_id' => $usuarioId, 'desactivado_por' => $actor->id, 'motivo' => $motivo]);

        // After the commit, so a failure closing sessions never undoes the deactivation; signing in is already refused.
        $this->cerrarSesiones->borrarSesiones($usuarioId);
    }
}
