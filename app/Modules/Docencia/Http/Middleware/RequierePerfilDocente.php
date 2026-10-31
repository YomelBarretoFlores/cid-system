<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Http\Middleware;

use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final readonly class RequierePerfilDocente
{
    public const string RECHAZO = 'Tu cuenta no está vinculada a un perfil docente del CILN.';

    public function __construct(private DocenteDelUsuario $docenteDelUsuario) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $docenteId = ($this->docenteDelUsuario)((int) $request->user()?->getAuthIdentifier());

        abort_if($docenteId === null, 403, self::RECHAZO);

        return $next($request);
    }
}
