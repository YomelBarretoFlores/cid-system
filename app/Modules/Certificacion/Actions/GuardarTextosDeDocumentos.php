<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Models\TextosDeDocumento;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class GuardarTextosDeDocumentos
{
    private const array CAMPOS = [
        'cierre_constancia_matricula' => ['la frase final de la constancia de matrícula', 300],
        'cierre_constancia_notas' => ['la frase final de la constancia de notas', 300],
        'escala_notas' => ['la nota de la escala de calificación', 200],
        'programa_acreditacion' => ['la modalidad por programa de la acreditación', 150],
        'suficiencia_acreditacion' => ['la modalidad por suficiencia de la acreditación', 150],
        'constancia_acta_acreditacion' => ['la frase del acta final de la acreditación', 300],
        'validez_acreditacion' => ['la frase de validez de la acreditación', 300],
        'texto_certificado' => ['el texto del certificado', 500],
    ];

    private const array MARCAS = [
        'validez_acreditacion' => ['{mencion}'],
        'texto_certificado' => ['{nivel}', '{idioma}'],
    ];

    public static function puedeGuardar(User $usuario): bool
    {
        return $usuario->can(PermisoCertificacion::Documentos->value);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function __invoke(array $datos, User $usuario): TextosDeDocumento
    {
        throw_unless(self::puedeGuardar($usuario), AuthorizationException::class);
        $limpios = $this->validar($datos);

        return Transaccion::ejecutar(function () use ($limpios): TextosDeDocumento {
            $textos = TextosDeDocumento::query()->whereKey(TextosDeDocumento::FILA)->lockForUpdate()->first() ?? new TextosDeDocumento;
            $textos->forceFill(['id' => TextosDeDocumento::FILA, ...$limpios])->save();

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
            $texto = trim(is_string($datos[$campo] ?? null) ? $datos[$campo] : '');
            throw_if($texto === '' || mb_strlen($texto) > $maximo, ReglaDeNegocioIncumplida::class, "Completa {$nombre}, hasta {$maximo} caracteres.");
            $this->exigirMarcas($campo, $nombre, $texto);
            $limpios[$campo] = $texto;
        }

        return $limpios;
    }

    private function exigirMarcas(string $campo, string $nombre, string $texto): void
    {
        foreach (self::MARCAS[$campo] ?? [] as $marca) {
            throw_unless(str_contains($texto, $marca), ReglaDeNegocioIncumplida::class, ucfirst($nombre)." debe incluir {$marca}.");
        }
    }
}
