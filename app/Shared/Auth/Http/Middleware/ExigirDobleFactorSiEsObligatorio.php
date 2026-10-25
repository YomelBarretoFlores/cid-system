<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Middleware;

use Closure;
use Filament\Auth\MultiFactor\Http\Middleware\EnsureMultiFactorAuthenticationIsEnabled;
use Illuminate\Http\Request;

// Filament fixes "required" when routes are registered; reading the setting here lets the DSTI switch it on without code (after config:cache, rebuild the cache).
final readonly class ExigirDobleFactorSiEsObligatorio
{
    public function __construct(private EnsureMultiFactorAuthenticationIsEnabled $exigir) {}

    public function handle(Request $request, Closure $next): mixed
    {
        if (! config()->boolean('auth.panel_doble_factor_obligatorio')) {
            return $next($request);
        }

        return $this->exigir->handle($request, $next);
    }
}
