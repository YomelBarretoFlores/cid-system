<?php

declare(strict_types=1);

namespace App\Modules\Personas\Livewire;

use App\Modules\Personas\Actions\RegistrarCuentaDeEstudiante;
use App\Modules\Personas\Data\SolicitudDeCuenta;
use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Models\Persona;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Livewire\MensajesDeValidacion;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Component;

#[Layout('components.layouts.acceso')]
#[Title('Crear cuenta')]
final class Registro extends Component
{
    use MensajesDeValidacion {
        messages as mensajesComunes;
        validationAttributes as atributosComunes;
    }

    // Students often register from one campus address, so the limit leaves room for a full lab.
    private const int INTENTOS_POR_MINUTO = 20;

    public string $tipo_documento = 'dni';

    public string $numero_documento = '';

    public string $apellido_paterno = '';

    public string $apellido_materno = '';

    public string $nombres = '';

    public string $fecha_nacimiento = '';

    public string $email = '';

    public string $celular = '';

    public string $password = '';

    public string $password_confirmation = '';

    public bool $acepta_tratamiento = false;

    public function registrarse(RegistrarCuentaDeEstudiante $registrar): void
    {
        $this->limitarIntentos();
        $this->normalizar();
        $this->validate();

        try {
            $user = $registrar($this->solicitud());
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
            $this->addError($error->getMessage() === RegistrarCuentaDeEstudiante::CORREO_EN_USO ? 'email' : 'numero_documento', $error->getMessage());

            return;
        }

        Auth::login($user);
        session()->regenerate();

        $this->redirectRoute('estudiante.inicio');
    }

    public function render(): View
    {
        return view('personas::livewire.registro', [
            'tiposDeDocumento' => collect(TipoDocumento::cases())->mapWithKeys(fn (TipoDocumento $tipo): array => [$tipo->value => $tipo->etiqueta()])->all(),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'tipo_documento' => ['required', Rule::enum(TipoDocumento::class)],
            'numero_documento' => ['required', 'string', $this->esDni() ? 'regex:/^[0-9]{8}$/' : 'regex:/^[A-Z0-9]{6,12}$/'],
            'apellido_paterno' => ['required', 'string', 'max:60'],
            'apellido_materno' => ['nullable', 'string', 'max:60'],
            'nombres' => ['required', 'string', 'max:80'],
            'fecha_nacimiento' => ['required', 'date_format:Y-m-d', 'after:1900-01-01', 'before:today'],
            'email' => ['required', 'string', 'email', 'max:150'],
            'celular' => ['required', 'string', 'regex:'.Persona::FORMATO_CELULAR],
            'password' => ['required', 'string', 'max:255', 'confirmed', Password::defaults()],
            'acepta_tratamiento' => ['accepted'],
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function messages(): array
    {
        $fechaInvalida = 'Escribe una fecha de nacimiento válida.';

        return [
            ...$this->mensajesComunes(),
            'numero_documento.regex' => $this->esDni() ? 'El DNI tiene 8 dígitos.' : 'El documento tiene de 6 a 12 letras o números.',
            'fecha_nacimiento.date_format' => $fechaInvalida,
            'fecha_nacimiento.after' => $fechaInvalida,
            'fecha_nacimiento.before' => $fechaInvalida,
            'celular.required' => 'Escribe tu número de celular.',
            'celular.regex' => 'Escribe un celular válido, solo con números.',
            'acepta_tratamiento.accepted' => 'Debes aceptar el tratamiento de tus datos personales para crear la cuenta.',
        ];
    }

    /**
     * @return array<string, string>
     */
    protected function validationAttributes(): array
    {
        return [
            ...$this->atributosComunes(),
            'tipo_documento' => 'tipo de documento',
            'numero_documento' => 'número de documento',
            'apellido_paterno' => 'apellido paterno',
            'apellido_materno' => 'apellido materno',
            'fecha_nacimiento' => 'fecha de nacimiento',
        ];
    }

    private function limitarIntentos(): void
    {
        $clave = 'registro|'.request()->ip();

        if (RateLimiter::tooManyAttempts($clave, self::INTENTOS_POR_MINUTO)) {
            throw ValidationException::withMessages([
                'registro' => 'Demasiados intentos. Vuelve a intentarlo en '.RateLimiter::availableIn($clave).' segundos.',
            ]);
        }

        RateLimiter::hit($clave);
    }

    private function normalizar(): void
    {
        $this->numero_documento = Persona::normalizarDocumento($this->numero_documento);
        $this->email = mb_strtolower(trim($this->email));
        $this->celular = Persona::normalizarCelular(trim($this->celular));
    }

    private function esDni(): bool
    {
        return $this->tipo_documento === TipoDocumento::Dni->value;
    }

    private function solicitud(): SolicitudDeCuenta
    {
        return new SolicitudDeCuenta(
            tipoDocumento: TipoDocumento::from($this->tipo_documento),
            numeroDocumento: $this->numero_documento,
            apellidoPaterno: $this->apellido_paterno,
            apellidoMaterno: $this->apellido_materno,
            nombres: $this->nombres,
            fechaNacimiento: CarbonImmutable::createFromFormat('Y-m-d', $this->fecha_nacimiento) ?: CarbonImmutable::now(),
            correo: $this->email,
            celular: $this->celular,
            aceptaTratamiento: $this->acepta_tratamiento,
            password: $this->password,
        );
    }
}
