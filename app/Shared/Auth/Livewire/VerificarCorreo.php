<?php

declare(strict_types=1);

namespace App\Shared\Auth\Livewire;

use App\Shared\Auth\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.acceso')]
#[Title('Verifica tu correo')]
final class VerificarCorreo extends Component
{
    public ?string $aviso = null;

    public function mount(): void
    {
        if ($this->usuario()->hasVerifiedEmail()) {
            $this->redirectRoute('estudiante.inicio');
        }
    }

    public function reenviar(): void
    {
        $clave = 'verificacion|'.$this->usuario()->getKey();

        if (RateLimiter::tooManyAttempts($clave, 1)) {
            $this->aviso = 'Espera un minuto antes de pedir otro enlace.';

            return;
        }

        RateLimiter::hit($clave);
        $this->usuario()->sendEmailVerificationNotification();
        $this->aviso = 'Te enviamos un nuevo enlace de verificación.';
    }

    public function render(): View
    {
        return view('auth.verificar-correo', ['correo' => $this->usuario()->email]);
    }

    private function usuario(): User
    {
        $usuario = auth()->user();

        return $usuario instanceof User ? $usuario : abort(403);
    }
}
