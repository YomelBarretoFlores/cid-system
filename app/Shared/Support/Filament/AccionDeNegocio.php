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
        try {
            $operacion(self::usuario());
        } catch (ReglaDeNegocioIncumplida $error) {
            self::notificarError($error);
            $action->halt();

            return;
        }

        Notification::make()->success()->title($exito)->send();
    }

    public static function usuario(): User
    {
        $usuario = auth()->user();

        return $usuario instanceof User ? $usuario : throw new AuthenticationException;
    }

    public static function notificarError(ReglaDeNegocioIncumplida $error): void
    {
        Notification::make()->danger()->title($error->getMessage())->send();
    }
}
