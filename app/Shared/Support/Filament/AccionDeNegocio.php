<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Illuminate\Auth\AuthenticationException;

final class AccionDeNegocio
{
    /**
     * @param  Closure(User): mixed  $operacion
     */
    public static function ejecutar(Action $action, Closure $operacion, string $exito): void
    {
        $usuario = auth()->user();

        if (! $usuario instanceof User) {
            throw new AuthenticationException;
        }

        try {
            $operacion($usuario);
        } catch (ReglaDeNegocioIncumplida $error) {
            Notification::make()->danger()->title($error->getMessage())->send();
            $action->halt();
        }

        Notification::make()->success()->title($exito)->send();
    }
}
