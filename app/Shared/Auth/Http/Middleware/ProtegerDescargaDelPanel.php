<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Middleware;

use App\Shared\Auth\Models\User;
use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Staff downloads live outside the panel routes, so they name the panel and ask for its session, timeout and required second factor.
final readonly class ProtegerDescargaDelPanel
{
    public function __construct(private ProtegerSesionDelPanel $sesionDelPanel, private ExigirDobleFactorSiEsObligatorio $dobleFactor) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $panel = Filament::getPanel('admin');
        Filament::setCurrentPanel($panel);
        $usuario = $request->user();

        // Checked first: the panel-session guard signs the whole account out, which must never happen to a portal account.
        abort_unless($usuario instanceof User && $usuario->canAccessPanel($panel), 403);

        return $this->sesionDelPanel->handle($request, fn (Request $solicitud): mixed => $this->dobleFactor->handle($solicitud, $next));
    }
}
