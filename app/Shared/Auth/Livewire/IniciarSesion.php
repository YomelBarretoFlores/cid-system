<?php

declare(strict_types=1);

namespace App\Shared\Auth\Livewire;

use App\Shared\Support\Livewire\MensajesDeValidacion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Ingresar')]
final class IniciarSesion extends Component
{
    use MensajesDeValidacion;

    public const string CREDENCIALES_INCORRECTAS = 'El correo o la contraseña no son correctos.';

    private const int INTENTOS_POR_MINUTO = 5;

    private const int INTENTOS_POR_DIRECCION = 20;

    public string $email = '';

    public string $password = '';

    public bool $recordar = false;

    public function ingresar(): void
    {
        $this->email = mb_strtolower(trim($this->email));
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        $clave = "ingreso|{$this->email}|".request()->ip();
        // The per-address key also stops one address from trying a common password on many accounts.
        $claveDeDireccion = 'ingreso|'.request()->ip();

        $this->asegurarIntentosDisponibles($clave, self::INTENTOS_POR_MINUTO);
        $this->asegurarIntentosDisponibles($claveDeDireccion, self::INTENTOS_POR_DIRECCION);

        if (! Auth::attempt(['email' => $this->email, 'password' => $this->password], $this->recordar)) {
            RateLimiter::hit($clave);
            RateLimiter::hit($claveDeDireccion);
            $this->reset('password');

            throw ValidationException::withMessages(['email' => self::CREDENCIALES_INCORRECTAS]);
        }

        RateLimiter::clear($clave);
        session()->regenerate();

        $this->redirectIntended(route('estudiante.inicio'));
    }

    public function render(): View
    {
        return view('auth.iniciar-sesion');
    }

    private function asegurarIntentosDisponibles(string $clave, int $maximo): void
    {
        if (RateLimiter::tooManyAttempts($clave, $maximo)) {
            throw ValidationException::withMessages([
                'email' => 'Demasiados intentos. Vuelve a intentarlo en '.RateLimiter::availableIn($clave).' segundos.',
            ]);
        }
    }
}
