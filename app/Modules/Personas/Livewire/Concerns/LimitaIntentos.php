<?php

declare(strict_types=1);

namespace App\Modules\Personas\Livewire\Concerns;

use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;

trait LimitaIntentos
{
    private function limitarIntentos(string $clave, int $maximo, int $segundos, string $campo): void
    {
        if (RateLimiter::tooManyAttempts($clave, $maximo)) {
            throw ValidationException::withMessages([
                $campo => 'Demasiados intentos. Vuelve a intentarlo en '.RateLimiter::availableIn($clave).' segundos.',
            ]);
        }

        RateLimiter::hit($clave, $segundos);
    }
}
