<?php

declare(strict_types=1);

namespace App\Shared\Auth\Livewire;

use App\Shared\Auth\Actions\AutenticarUsuario;
use App\Shared\Auth\Enums\EntradaDelPortal;
use App\Shared\Auth\Support\DestinoTrasIngreso;
use App\Shared\Support\Livewire\MensajesDeValidacion;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Component;

#[Layout('components.layouts.acceso')]
final class IniciarSesion extends Component
{
    use MensajesDeValidacion;

    public const string CREDENCIALES_INCORRECTAS = AutenticarUsuario::CREDENCIALES_INCORRECTAS;

    #[Locked]
    public EntradaDelPortal $entrada = EntradaDelPortal::Estudiante;

    public string $email = '';

    public string $password = '';

    public bool $recordar = false;

    public function mount(?string $portal = null): void
    {
        $this->entrada = $portal === null ? EntradaDelPortal::delPedido(request()) : EntradaDelPortal::desde($portal);
    }

    public function ingresar(): void
    {
        $this->email = mb_strtolower(trim($this->email));
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255'],
        ]);

        try {
            $usuario = app(AutenticarUsuario::class)($this->email, $this->password, (string) request()->ip());
        } catch (ValidationException $error) {
            // As before, only wrong credentials clear the password; the attempt limit keeps it.
            if (in_array(self::CREDENCIALES_INCORRECTAS, $error->errors()['email'] ?? [], true)) {
                $this->reset('password');
            }

            throw $error;
        }

        Auth::login($usuario, $this->recordar);
        session()->regenerate();

        $this->redirectIntended(app(DestinoTrasIngreso::class)->para($usuario, $this->entrada));
    }

    public function render(): View
    {
        return view('auth.iniciar-sesion')->title('Ingresar');
    }
}
