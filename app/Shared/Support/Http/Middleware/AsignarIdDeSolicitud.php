<?php

declare(strict_types=1);

namespace App\Shared\Support\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

// One ID ties a request's log lines, its audit rows and the response a user can report.
final class AsignarIdDeSolicitud
{
    public const string CABECERA = 'X-Request-Id';

    public function handle(Request $request, Closure $next): Response
    {
        $recibido = $request->headers->get(self::CABECERA);
        $id = is_string($recibido) && preg_match('/^[A-Za-z0-9-]{8,64}\z/', $recibido) === 1 ? $recibido : (string) Str::ulid();

        Context::add('solicitud_id', $id);

        $respuesta = $next($request);
        $respuesta->headers->set(self::CABECERA, $id);

        return $respuesta;
    }
}
