<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Filament\Notifications\Notification;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Only the panel sign-in asks the second factor, so a session opened elsewhere (portal, "remember me") is refused.
final class ProtegerSesionDelPanel
{
    public const string ULTIMA_ACTIVIDAD = 'panel.ultima_actividad';

    /**
     * @return array{usuario: int|string, instante: int}
     */
    public static function marca(int|string $usuarioId): array
    {
        return ['usuario' => $usuarioId, 'instante' => now()->getTimestamp()];
    }

    public static function registrarActividad(): void
    {
        $usuarioId = Filament::auth()->id();

        if ($usuarioId !== null) {
            session()->put(self::ULTIMA_ACTIVIDAD, self::marca($usuarioId));
        }
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $marca = $request->session()->get(self::ULTIMA_ACTIVIDAD);

        // The mark names its account: a later sign-in of another account in the same browser starts unmarked.
        if (! is_array($marca) || ($marca['usuario'] ?? null) !== Filament::auth()->id() || ! is_int($marca['instante'] ?? null)) {
            return $this->cerrar($request, 'Ingresa al panel con tu correo, tu contraseña y el código de tu aplicación.');
        }

        if (now()->getTimestamp() - $marca['instante'] > max(1, config()->integer('session.panel_lifetime')) * 60) {
            return $this->cerrar($request, 'Tu sesión del panel se cerró por inactividad. Vuelve a ingresar.');
        }

        self::registrarActividad();

        return $next($request);
    }

    private function cerrar(Request $request, string $motivo): Response
    {
        Filament::auth()->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        Notification::make()->title($motivo)->warning()->send();

        return redirect()->to(Filament::getLoginUrl() ?? '/');
    }
}
