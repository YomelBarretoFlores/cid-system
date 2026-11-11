<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Closure;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Support\Exceptions\Halt;
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

    // Shows the rule to the user and stops the page or action, so the save never ends in a server error.
    public static function detener(ReglaDeNegocioIncumplida $error): never
    {
        self::notificarError($error);

        // A rejected save must not commit earlier writes of a transaction Filament may have opened around it.
        throw new Halt($error->getMessage(), $error->getCode(), $error)->rollBackDatabaseTransaction();
    }

    public static function notificarError(ReglaDeNegocioIncumplida $error): void
    {
        RegistroDeRechazos::registrar($error);
        Notification::make()->danger()->title($error->getMessage())->send();
    }
}
