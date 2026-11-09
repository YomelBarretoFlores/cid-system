<?php

declare(strict_types=1);

namespace App\Modules\Personas\Livewire;

use App\Modules\Personas\Actions\ActualizarDatosDeContacto;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Modules\Personas\Livewire\Concerns\LimitaIntentos;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Actions\CambiarContrasena;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Livewire\MensajesDeValidacion;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Auth\SessionGuard;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.portal')]
#[Title('Modificar datos')]
final class ModificarDatos extends Component
{
    use LimitaIntentos;
    use MensajesDeValidacion {
        messages as mensajesComunes;
        validationAttributes as atributosComunes;
    }

    private const int CAMBIOS_DE_CONTRASENA_POR_MINUTO = 5;

    private const int GUARDADOS_DE_CONTACTO_POR_MINUTO = 10;

    public string $celular = '';

    public string $direccion = '';

    public string $password_actual = '';

    public string $password = '';

    public string $password_confirmation = '';

    public function mount(): void
    {
        $ficha = $this->ficha();
        $this->celular = (string) $ficha->celular;
        $this->direccion = (string) $ficha->direccion;
    }

    public function guardarContacto(ActualizarDatosDeContacto $actualizar): void
    {
        $this->celular = Persona::normalizarCelular(trim($this->celular));
        $this->validate([
            'celular' => ['nullable', 'string', 'regex:'.Persona::FORMATO_CELULAR],
            'direccion' => ['nullable', 'string', 'max:'.ActualizarDatosDeContacto::DIRECCION_MAXIMA],
        ]);
        $this->limitarIntentos('datos-de-contacto|'.auth()->id(), self::GUARDADOS_DE_CONTACTO_POR_MINUTO, 60, 'contacto');

        try {
            $actualizar($this->ficha()->personaId, $this->celular, $this->direccion);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('contacto', $error->getMessage());

            return;
        }

        session()->flash('estado', 'Guardamos tus datos de contacto.');
        $this->redirectRoute('estudiante.perfil.datos');
    }

    public function cambiarContrasena(CambiarContrasena $cambiar): void
    {
        $usuarioId = (int) auth()->id();
        $this->limitarIntentos("cambio-de-contrasena|{$usuarioId}", self::CAMBIOS_DE_CONTRASENA_POR_MINUTO, 60, 'password_actual');
        $this->validate([
            'password_actual' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::defaults()],
        ]);

        try {
            $usuario = $cambiar($usuarioId, $this->password_actual, $this->password);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError('password_actual', $error->getMessage());

            return;
        }

        $this->conservarEstaSesion($usuario);
        session()->flash('estado', 'Cambiamos tu contraseña y cerramos tus sesiones en otros dispositivos.');
        $this->redirectRoute('estudiante.perfil.datos');
    }

    public function render(): View
    {
        return view('personas::livewire.modificar-datos', ['ficha' => $this->ficha()]);
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        return [
            ...$this->mensajesComunes(),
            'celular.regex' => 'Escribe un celular válido, solo con números.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            ...$this->atributosComunes(),
            'direccion' => 'dirección',
            'password_actual' => 'contraseña actual',
            'password' => 'nueva contraseña',
        ];
    }

    // Livewire updates skip auth.session, so this request must store the new hash or the next page would sign the student out.
    private function conservarEstaSesion(User $usuario): void
    {
        /** @var SessionGuard $guardia */
        $guardia = Auth::guard('web');
        $guardia->setUser($usuario);
        $guardia->logoutOtherDevices($this->password);
        session()->put('password_hash_web', $guardia->hashPasswordForCookie($usuario->getAuthPassword()));
        $this->reset('password_actual', 'password', 'password_confirmation');
    }

    private function ficha(): FichaDeEstudiante
    {
        return app(FichaDelUsuario::class)((int) auth()->id()) ?? abort(403);
    }
}
