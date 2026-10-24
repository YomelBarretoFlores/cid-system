<?php

declare(strict_types=1);

namespace App\Shared\Auth\Http\Controllers;

use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

final class ConfirmarCorreo
{
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        $request->fulfill();

        return to_route('estudiante.inicio')->with('estado', 'Tu correo quedó verificado.');
    }
}
