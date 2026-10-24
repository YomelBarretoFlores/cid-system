<?php

declare(strict_types=1);

namespace App\Shared\Auth\Livewire;

use App\Shared\Support\Livewire\MensajesDeValidacion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Recuperar contraseña')]
final class SolicitarRestablecimiento extends Component
{
    use MensajesDeValidacion;

    private const int SOLICITUDES_POR_MINUTO = 5;

    public string $email = '';

    public bool $enviado = false;

    public function enviar(): void
    {
        $this->email = mb_strtolower(trim($this->email));
        $this->validate(['email' => ['required', 'string', 'email', 'max:255']]);

        $clave = 'restablecimiento|'.request()->ip();

        if (RateLimiter::tooManyAttempts($clave, self::SOLICITUDES_POR_MINUTO)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiadas solicitudes. Vuelve a intentarlo en '.RateLimiter::availableIn($clave).' segundos.',
            ]);
        }

        RateLimiter::hit($clave);
        // The answer is the same whether or not the address exists, so the form cannot be used to find accounts.
        Password::sendResetLink(['email' => $this->email]);

        $this->enviado = true;
    }

    public function render(): View
    {
        return view('auth.solicitar-restablecimiento');
    }
}
