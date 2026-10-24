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
        // The ID is always generated here, so a client cannot choose what the audit trail records.
        $id = (string) Str::ulid();
        $recibido = $request->headers->get(self::CABECERA);

        Context::add('solicitud_id', $id);

        if (is_string($recibido) && preg_match('/^[A-Za-z0-9-]{8,64}\z/', $recibido) === 1) {
            Context::add('solicitud_origen', $recibido);
        }

        $respuesta = $next($request);
        $respuesta->headers->set(self::CABECERA, $id);

        return $respuesta;
    }
}
