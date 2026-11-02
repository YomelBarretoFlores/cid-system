<?php

declare(strict_types=1);

namespace App\Shared\Auth\Actions;

use App\Shared\Auth\Models\User;
use App\Shared\Auth\Support\EnlaceDeContrasena;
use App\Shared\Auth\Support\GestoresDeUsuarios;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class EnviarEnlaceDeContrasena
{
    public function __invoke(int $usuarioId, User $actor): void
    {
        GestoresDeUsuarios::autorizar($actor);
        $usuario = User::query()->findOrFail($usuarioId);
        throw_if($usuario->desactivado_en !== null, ReglaDeNegocioIncumplida::class, 'Reactiva la cuenta antes de enviarle el enlace.');

        EnlaceDeContrasena::enviar($usuario->email);
    }
}
