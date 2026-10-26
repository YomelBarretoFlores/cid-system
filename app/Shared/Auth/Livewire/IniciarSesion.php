<?php

declare(strict_types=1);

namespace App\Shared\Auth\Livewire;

use App\Shared\Auth\Actions\AutenticarUsuario;
use App\Shared\Auth\Exceptions\CredencialesIncorrectas;
use App\Shared\Auth\Support\DestinoTrasIngreso;
use App\Shared\Support\Livewire\MensajesDeValidacion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('components.layouts.acceso')]
final class IniciarSesion extends Component
{
    use MensajesDeValidacion;

    public const string CREDENCIALES_INCORRECTAS = AutenticarUsuario::CREDENCIALES_INCORRECTAS;

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

        try {
            $usuario = app(AutenticarUsuario::class)($this->email, $this->password, (string) request()->ip());
        } catch (CredencialesIncorrectas $error) {
            // As before, only wrong credentials clear the password; the attempt limit keeps it.
            $this->reset('password');

            throw $error;
        }

        Auth::login($usuario, $this->recordar);
        session()->regenerate();

        $this->redirect(app(DestinoTrasIngreso::class)->trasIngresar($usuario, session()->pull('url.intended')));
    }

    public function render(): View
    {
        return view('auth.iniciar-sesion')->title('Ingresar');
    }
}
