<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Data\SolicitudDeCuenta;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Events\Registered;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class RegistrarCuentaDeEstudiante
{
    public const string CORREO_EN_USO = 'Ya existe una cuenta con ese correo.';

    // One message whether or not the record already has an account, so the form reveals no more than that.
    public const string DOCUMENTO_NO_DISPONIBLE = 'No podemos crear una cuenta con ese documento. Si ya tienes una, ingresa o recupera tu contraseña; si no, acércate a la oficina del CILN con tu documento de identidad para que te creen la cuenta.';

    public function __construct(private ObtenerVinculoPorDefecto $vinculoPorDefecto) {}

    public function __invoke(SolicitudDeCuenta $solicitud): User
    {
        try {
            $user = Transaccion::ejecutar(fn (): User => $this->registrar($solicitud));
        } catch (UniqueConstraintViolationException $error) {
            throw $this->traducirDuplicado($error);
        }

        event(new Registered($user));

        return $user;
    }

    private function registrar(SolicitudDeCuenta $solicitud): User
    {
        $correo = mb_strtolower(trim($solicitud->correo));

        if (User::query()->where('email', $correo)->exists()) {
            throw new ReglaDeNegocioIncumplida(self::CORREO_EN_USO);
        }

        $persona = $this->nuevaPersona($solicitud, $correo);
        $user = User::query()->create([
            'name' => "{$persona->nombres} {$persona->apellido_paterno}",
            'email' => $correo,
            'password' => $solicitud->password,
        ]);

        $persona->user()->associate($user);
        $persona->tratamiento_aceptado_en = $solicitud->aceptaTratamiento ? now() : null;
        $persona->habilitada_en = null;
        $persona->save();

        return $user;
    }

    // Without email verification nothing proves an existing record belongs to whoever registers it, so staff create those accounts at the office.
    private function nuevaPersona(SolicitudDeCuenta $solicitud, string $correo): Persona
    {
        $existe = Persona::query()
            ->where('tipo_documento', $solicitud->tipoDocumento)
            ->where('numero_documento', Persona::normalizarDocumento($solicitud->numeroDocumento))
            ->exists();

        throw_if($existe, ReglaDeNegocioIncumplida::class, self::DOCUMENTO_NO_DISPONIBLE);

        return Persona::query()->create([
            'tipo_documento' => $solicitud->tipoDocumento,
            'numero_documento' => $solicitud->numeroDocumento,
            'apellido_paterno' => trim($solicitud->apellidoPaterno),
            'apellido_materno' => filled($solicitud->apellidoMaterno) ? trim($solicitud->apellidoMaterno) : null,
            'nombres' => trim($solicitud->nombres),
            'fecha_nacimiento' => $solicitud->fechaNacimiento,
            'correo' => $correo,
            'celular' => filled($solicitud->celular) ? $solicitud->celular : null,
            'tipo_vinculo' => ($this->vinculoPorDefecto)(),
        ]);
    }

    private function traducirDuplicado(UniqueConstraintViolationException $error): ReglaDeNegocioIncumplida|UniqueConstraintViolationException
    {
        return match (true) {
            RestriccionUnica::es($error, 'users_email_unique') => new ReglaDeNegocioIncumplida(self::CORREO_EN_USO, previous: $error),
            RestriccionUnica::es($error, 'per_personas_tipo_documento_numero_documento_unique') => new ReglaDeNegocioIncumplida(self::DOCUMENTO_NO_DISPONIBLE, previous: $error),
            default => $error,
        };
    }
}
