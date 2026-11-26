<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Enums\PermisoPersona;
use App\Modules\Personas\Models\TextosLegales;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class GuardarTextosLegales
{
    private const array CAMPOS = [
        'politica_privacidad' => ['la política de privacidad', 20000],
        'domicilio_titular' => ['el domicilio del titular', 200],
        'consentimiento_registro' => ['la frase de consentimiento', 600],
    ];

    public function __construct(private ConsultarTextosLegales $consultar) {}

    public static function puedeGuardar(User $usuario): bool
    {
        return $usuario->can(PermisoPersona::Gestionar->value);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function __invoke(array $datos, User $usuario): TextosLegales
    {
        throw_unless(self::puedeGuardar($usuario), AuthorizationException::class);
        $limpios = $this->validar($datos);

        return Transaccion::ejecutar(function () use ($limpios): TextosLegales {
            $textos = TextosLegales::query()->whereKey(TextosLegales::FILA)->lockForUpdate()->first();
            $anteriores = ($this->consultar)();
            $cambioLaPolitica = $anteriores->politica !== $limpios['politica_privacidad'] || $anteriores->domicilio !== $limpios['domicilio_titular'];
            $fecha = $cambioLaPolitica ? now('America/Lima')->toDateString() : $anteriores->actualizadaEl;

            $textos ??= new TextosLegales;
            $textos->forceFill(['id' => TextosLegales::FILA, ...$limpios, 'politica_actualizada_el' => $fecha])->save();

            return $textos;
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, string>
     */
    private function validar(array $datos): array
    {
        $limpios = [];

        foreach (self::CAMPOS as $campo => [$nombre, $maximo]) {
            $texto = trim(is_string($datos[$campo] ?? null) ? str_replace("\r\n", "\n", $datos[$campo]) : '');
            throw_if($texto === '' || mb_strlen($texto) > $maximo, ReglaDeNegocioIncumplida::class, "Completa {$nombre}, hasta {$maximo} caracteres.");
            throw_if($campo === 'consentimiento_registro' && ! str_contains($texto, '{politica}'), ReglaDeNegocioIncumplida::class, 'La frase de consentimiento debe incluir {politica}, donde va el enlace a la política de privacidad.');
            $limpios[$campo] = $texto;
        }

        return $limpios;
    }
}
