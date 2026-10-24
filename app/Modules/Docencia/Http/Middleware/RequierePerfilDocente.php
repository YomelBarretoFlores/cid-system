<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Http\Middleware;

use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequierePerfilDocente
{
    public function __construct(private DocenteDelUsuario $docenteDelUsuario) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $docenteId = ($this->docenteDelUsuario)((int) $request->user()?->getAuthIdentifier());

        abort_if($docenteId === null, 403, 'Tu cuenta no está vinculada a un perfil docente del CILN.');

        return $next($request);
    }
}
