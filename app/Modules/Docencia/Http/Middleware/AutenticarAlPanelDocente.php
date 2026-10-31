<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Http\Middleware;

use App\Modules\Docencia\Contracts\DocenteDelUsuario;
use Filament\Facades\Filament;
use Filament\Http\Middleware\Authenticate;

// Laravel runs authentication before any other middleware, so the refusal of an account without a teacher profile is explained here.
final class AutenticarAlPanelDocente extends Authenticate
{
    protected function authenticate($request, array $guards): void
    {
        $usuarioId = Filament::auth()->id();

        abort_if($usuarioId !== null && app(DocenteDelUsuario::class)((int) $usuarioId) === null, 403, RequierePerfilDocente::RECHAZO);

        parent::authenticate($request, $guards);
    }
}
