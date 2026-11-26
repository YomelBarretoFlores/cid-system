<?php

declare(strict_types=1);

namespace App\Modules\Personas\Http\Middleware;

use App\Modules\Personas\Models\Persona;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequiereCuentaHabilitada
{
    public const string MENSAJE = 'Tu cuenta aún no está habilitada. Acércate al CILN con tu recibo de pago.';

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $habilitada = Persona::query()
            ->where('user_id', $request->user()?->getAuthIdentifier())
            ->whereNotNull('habilitada_en')
            ->exists();

        if ($habilitada) {
            return $next($request);
        }

        abort_if($request->expectsJson(), 403, self::MENSAJE);

        return redirect()->route('estudiante.inicio');
    }
}
