<?php

declare(strict_types=1);

namespace App\Modules\Personas\Http\Middleware;

use App\Modules\Personas\Models\Persona;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequiereFichaDeEstudiante
{
    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $tieneFicha = Persona::query()->where('user_id', $request->user()?->getAuthIdentifier())->exists();

        abort_unless($tieneFicha, 403, 'Tu cuenta no está vinculada a una ficha de estudiante del CILN.');

        return $next($request);
    }
}
