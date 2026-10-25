<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// The API counterpart of the web 'verified' gate, with the refusal in Spanish.
final readonly class RequiereCorreoVerificado
{
    public function handle(Request $request, Closure $next): Response
    {
        $usuario = $request->user();

        abort_unless($usuario instanceof MustVerifyEmail && $usuario->hasVerifiedEmail(), 403, 'Verifica tu correo antes de usar estos datos.');

        return $next($request);
    }
}
