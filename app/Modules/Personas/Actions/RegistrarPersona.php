<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Data\DatosDePersona;
use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\Persona;
use App\Modules\Personas\Models\TipoVinculo;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\RestriccionUnica;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\UniqueConstraintViolationException;

final readonly class RegistrarPersona
{
    public const string DOCUMENTO_REPETIDO = 'Ya existe una persona registrada con ese documento.';

    public const string CELULAR_INVALIDO = 'El celular debe tener de 7 a 15 dígitos y puede empezar con +.';

    public function __construct(private ComprobarDocumento $comprobarDocumento) {}

    public function __invoke(DatosDePersona $datos, User $actor): Persona
    {
        throw_unless($actor->can(PermisoPersona::Gestionar->value), AuthorizationException::class);

        $persona = new Persona([
            'tipo_documento' => $datos->tipoDocumento,
            'numero_documento' => $datos->numeroDocumento,
            'apellido_paterno' => trim($datos->apellidoPaterno),
            'apellido_materno' => $this->opcional($datos->apellidoMaterno),
            'nombres' => trim($datos->nombres),
            'correo' => $this->opcional($datos->correo),
            'celular' => $this->opcional($datos->celular),
            'fecha_nacimiento' => $this->opcional($datos->fechaNacimiento),
            'tipo_vinculo' => $datos->tipoVinculo ?? app(ObtenerVinculoPorDefecto::class)(),
        ]);
        $this->asegurar($persona);
        $persona->registrarConsentimiento($datos->aceptaTratamiento);

        try {
            $persona->save();
        } catch (UniqueConstraintViolationException $error) {
            throw RestriccionUnica::es($error, 'per_personas_tipo_documento_numero_documento_unique') ? new ReglaDeNegocioIncumplida(self::DOCUMENTO_REPETIDO) : $error;
        }

        return $persona;
    }

    private function asegurar(Persona $persona): void
    {
        ($this->comprobarDocumento)($persona);

        $reglas = [
            'Escribe el apellido paterno y los nombres.' => $persona->apellido_paterno !== '' && $persona->nombres !== '',
            'Escribe un correo personal válido.' => $persona->correo === null || filter_var($persona->correo, FILTER_VALIDATE_EMAIL) !== false,
            self::CELULAR_INVALIDO => $persona->celular === null || preg_match(Persona::FORMATO_CELULAR, $persona->celular) === 1,
            'El tipo de vínculo elegido no está disponible.' => TipoVinculo::query()->where(['codigo' => $persona->tipo_vinculo, 'activo' => true])->exists(),
            self::DOCUMENTO_REPETIDO => ! Persona::query()->where(['tipo_documento' => $persona->tipo_documento, 'numero_documento' => $persona->numero_documento])->exists(),
        ];

        foreach ($reglas as $mensaje => $cumple) {
            if (! $cumple) {
                throw new ReglaDeNegocioIncumplida($mensaje);
            }
        }
    }

    private function opcional(?string $valor): ?string
    {
        $valor = $valor === null ? null : trim($valor);

        return $valor === '' ? null : $valor;
    }
}
