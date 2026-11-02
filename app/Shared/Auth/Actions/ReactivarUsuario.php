<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\GestoresDeUsuarios;
use App\Shared\Support\Database\Transaccion;
use Illuminate\Support\Facades\Log;

final readonly class ReactivarUsuario
{
    public function __invoke(int $usuarioId, User $actor): void
    {
        $reactivada = Transaccion::ejecutar(function () use ($usuarioId, $actor): bool {
            GestoresDeUsuarios::autorizar($actor);
            $usuario = User::query()->whereKey($usuarioId)->lockForUpdate()->firstOrFail();

            if ($usuario->desactivado_en === null) {
                return false;
            }

            $usuario->forceFill(['desactivado_en' => null])->save();

            return true;
        });

        if ($reactivada) {
            Log::info('Se reactivó una cuenta del personal.', ['usuario_id' => $usuarioId, 'reactivado_por' => $actor->id]);
        }
    }
}
