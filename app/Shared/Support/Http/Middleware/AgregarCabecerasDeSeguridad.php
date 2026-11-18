<?php

declare(strict_types=1);

namespace App\Shared\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Vite;
use Symfony\Component\HttpFoundation\Response;

final class AgregarCabecerasDeSeguridad
{
    // Livewire and Alpine evaluate inline expressions ('unsafe-inline', 'unsafe-eval'); Filament's file upload starts its workers from blob: URLs.
    public const string POLITICA_DE_CONTENIDO = "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline'; img-src 'self' data: blob:; font-src 'self' data:; connect-src 'self'; worker-src 'self' blob:; frame-ancestors 'self'; base-uri 'self'; form-action 'self'; object-src 'none'";

    private const array CABECERAS = [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Permissions-Policy' => 'camera=(), microphone=(), geolocation=()',
        'Cross-Origin-Opener-Policy' => 'same-origin',
        'Cross-Origin-Resource-Policy' => 'same-origin',
    ];

    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $respuesta */
        $respuesta = $next($request);
        $cabeceras = self::CABECERAS;

        if ($request->isSecure()) {
            $cabeceras['Strict-Transport-Security'] = 'max-age=31536000';
        }

        if ($this->aplicaPoliticaDeContenido($request)) {
            $cabeceras['Content-Security-Policy'] = self::POLITICA_DE_CONTENIDO;
        }

        foreach ($cabeceras as $nombre => $valor) {
            if (! $respuesta->headers->has($nombre)) {
                $respuesta->headers->set($nombre, $valor);
            }
        }

        return $respuesta;
    }

    // The Vite dev server and the API docs viewer (scripts from unpkg.com) load assets from other origins.
    private function aplicaPoliticaDeContenido(Request $request): bool
    {
        if (Vite::isRunningHot()) {
            return false;
        }

        return ! str_starts_with((string) $request->route()?->getName(), 'scramble.');
    }
}
