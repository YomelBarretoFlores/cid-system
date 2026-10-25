<?php

declare(strict_types=1);

namespace App\Shared\Auth\Livewire;

use App\Shared\Auth\Models\User;
use App\Shared\Support\Livewire\MensajesDeValidacion;
use Illuminate\Auth\Events\PasswordReset;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password as ReglaDeClave;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Locked;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.acceso')]
#[Title('Nueva contraseña')]
final class RestablecerClave extends Component
{
    use MensajesDeValidacion;

    #[Locked]
    public string $token = '';

    public string $email = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(string $token): void
    {
        $this->token = $token;
        $this->email = request()->string('email')->lower()->trim()->value();
    }

    public function restablecer(): void
    {
        $this->email = mb_strtolower(trim($this->email));
        $this->validate([
            'email' => ['required', 'string', 'email', 'max:255'],
            'password' => ['required', 'string', 'max:255', 'confirmed', ReglaDeClave::defaults()],
        ]);

        $estado = Password::reset(
            ['email' => $this->email, 'password' => $this->password, 'token' => $this->token],
            function (User $user, string $password): void {
                $user->forceFill(['password' => $password, 'remember_token' => Str::random(60)])->save();

                event(new PasswordReset($user));
            },
        );

        if ($estado !== Password::PASSWORD_RESET) {
            $this->addError('email', 'El enlace no es válido o ya venció. Solicita uno nuevo.');

            return;
        }

        session()->flash('estado', 'Tu contraseña fue restablecida. Ya puedes ingresar.');
        $this->redirectRoute('estudiante.ingresar');
    }

    public function render(): View
    {
        return view('auth.restablecer-clave');
    }
}
