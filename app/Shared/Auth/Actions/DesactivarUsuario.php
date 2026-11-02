<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\GestoresDeUsuarios;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

// Accounts are deactivated, never deleted: their audit trail and the records they signed keep pointing at them.
final readonly class DesactivarUsuario
{
    public const int MAXIMO_DEL_MOTIVO = 500;

    public function __construct(private CerrarSesionesDeCuenta $cerrarSesiones) {}

    public function __invoke(int $usuarioId, string $motivo, User $actor): void
    {
        $motivo = trim($motivo);
        throw_if($motivo === '' || mb_strlen($motivo) > self::MAXIMO_DEL_MOTIVO, ReglaDeNegocioIncumplida::class, 'Escribe el motivo de la desactivación, de hasta 500 caracteres.');

        if (! Transaccion::ejecutar(fn (): bool => $this->desactivar($usuarioId, $actor))) {
            return;
        }

        Log::info('Se desactivó una cuenta del personal.', ['usuario_id' => $usuarioId, 'desactivado_por' => $actor->id, 'motivo' => $motivo]);

        // After the commit, so a failure closing sessions never undoes the deactivation; signing in is already refused.
        $this->cerrarSesiones->borrarSesiones($usuarioId);
    }

    private function desactivar(int $usuarioId, User $actor): bool
    {
        GestoresDeUsuarios::bloquear();
        GestoresDeUsuarios::autorizar($actor);
        throw_if($usuarioId === $actor->id, ReglaDeNegocioIncumplida::class, 'No puedes desactivar tu propia cuenta.');

        $usuario = User::query()->whereKey($usuarioId)->lockForUpdate()->firstOrFail();

        if ($usuario->desactivado_en !== null) {
            return false;
        }

        $usuario->forceFill(['desactivado_en' => now(), 'remember_token' => Str::random(60)])->save();
        $usuario->tokens()->delete();
        GestoresDeUsuarios::asegurarQueQuedeUno();

        return true;
    }
}
