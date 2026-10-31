<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament;

use Filament\Auth\Http\Responses\Contracts\LogoutResponse;
use Filament\Facades\Filament;
use Illuminate\Http\RedirectResponse;

// The teacher panel has no sign-in of its own: leaving it goes back to the single portal entry.
final class SalidaDelPanel implements LogoutResponse
{
    public function toResponse($request): RedirectResponse
    {
        return redirect()->to(Filament::hasLogin() ? (string) Filament::getLoginUrl() : route('ingresar'));
    }
}
