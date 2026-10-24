<?php

declare(strict_types=1);

namespace App\Shared\Audit\Http\Middleware;

use App\Shared\Audit\ContextoAuditoria;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Context;
use Symfony\Component\HttpFoundation\Response;

final readonly class EstablecerContextoAuditoria
{
    public function __construct(private ContextoAuditoria $contexto) {}

    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user() ?? $request->user('sanctum');

        $solicitud = Context::get('solicitud_id');

        $this->contexto->establecer($usuario?->getAuthIdentifier(), $request->ip(), is_string($solicitud) ? $solicitud : null);

        return $next($request);
    }
}
