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

    public static function registrarActividad(): void
    {
        session()->put(self::ULTIMA_ACTIVIDAD, now()->getTimestamp());
    }

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $ultimaActividad = $request->session()->get(self::ULTIMA_ACTIVIDAD);

        if (! is_int($ultimaActividad)) {
            return $this->cerrar($request, 'Ingresa al panel con tu correo, tu contraseña y el código de tu aplicación.');
        }

        if (now()->getTimestamp() - $ultimaActividad > config()->integer('session.panel_lifetime') * 60) {
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
