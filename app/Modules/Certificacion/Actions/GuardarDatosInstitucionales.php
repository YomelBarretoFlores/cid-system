<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\Institucion;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class GuardarDatosInstitucionales
{
    private const array FORMATOS = ['formato_numero', 'formato_numero_constancia_notas'];

    private const array CAMPOS = [
        'universidad' => ['la universidad', 150], 'lema' => ['el lema', 150], 'centro' => ['el centro', 150], 'direccion' => ['la dirección', 200],
        'telefono' => ['el teléfono', 100], 'web' => ['la web', 100], 'facebook' => ['el Facebook', 100], 'ciudad' => ['la ciudad', 60],
        'formato_numero' => ['el formato del número', 60], 'formato_numero_constancia_notas' => ['el formato del número de la constancia de notas', 60],
    ];

    // Actas print the same header, so changing it needs the actas permission besides the documents one.
    public static function puedeGuardar(User $usuario): bool
    {
        return $usuario->can(PermisoCertificacion::Documentos->value) && $usuario->can(PermisoCertificacion::Actas->value);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function __invoke(array $datos, User $usuario): Institucion
    {
        throw_unless(self::puedeGuardar($usuario), AuthorizationException::class);
        $limpios = $this->validar($datos);

        return Transaccion::ejecutar(function () use ($limpios): Institucion {
            $institucion = Institucion::query()->whereKey(Institucion::FILA)->lockForUpdate()->first() ?? new Institucion(['id' => Institucion::FILA]);
            $institucion->forceFill(['id' => Institucion::FILA, ...$limpios])->save();

            return $institucion;
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
            $texto = trim(is_string($datos[$campo] ?? null) ? $datos[$campo] : '');
            throw_if($texto === '' || mb_strlen($texto) > $maximo, ReglaDeNegocioIncumplida::class, "Completa {$nombre}, hasta {$maximo} caracteres.");
            $limpios[$campo] = $texto;
        }

        foreach (self::FORMATOS as $formato) {
            throw_unless(preg_match('/\{numero(:\d{1,2})?\}/', $limpios[$formato] ?? '') === 1 && preg_match('/\{numero:\d{3,}\}/', $limpios[$formato] ?? '') === 0, ReglaDeNegocioIncumplida::class, 'El formato del número debe incluir {numero} o {numero:N}, con N de hasta dos cifras.');
        }

        return $limpios;
    }
}
