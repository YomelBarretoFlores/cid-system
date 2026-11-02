<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Middleware;

use Closure;
use Filament\Facades\Filament;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

// Staff downloads live outside the panel routes, so they name the panel before asking for its session (second factor, timeout).
final readonly class ProtegerDescargaDelPanel
{
    public function __construct(private ProtegerSesionDelPanel $sesionDelPanel) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        Filament::setCurrentPanel('admin');

        return $this->sesionDelPanel->handle($request, $next);
    }
}
