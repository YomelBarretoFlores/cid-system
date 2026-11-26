<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Models\TextosDePortada;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Auth\Access\AuthorizationException;

final readonly class GuardarTextosDePortada
{
    private const int MAXIMO_DE_FILAS = 12;

    /** @var array<string, array{string, array<string, int>}> list => [name, field => max length] */
    private const array LISTAS = [
        'pasos_matricula' => ['los pasos de la matrícula', ['titulo' => 80, 'detalle' => 300]],
        'pasos_clasificacion' => ['los pasos del examen de clasificación', ['titulo' => 80, 'detalle' => 300]],
        'niveles' => ['los niveles', ['nombre' => 40, 'descripcion' => 300, 'logro' => 300]],
        'preguntas' => ['las preguntas frecuentes', ['pregunta' => 150, 'respuesta' => 600]],
    ];

    public static function puedeGuardar(User $usuario): bool
    {
        return $usuario->can(PermisoTramite::Configurar->value);
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public function __invoke(array $datos, User $usuario): TextosDePortada
    {
        throw_unless(self::puedeGuardar($usuario), AuthorizationException::class);
        $limpios = [...$this->listas($datos), 'nosotros' => $this->nosotros($datos)];

        return Transaccion::ejecutar(function () use ($limpios): TextosDePortada {
            $textos = TextosDePortada::query()->whereKey(TextosDePortada::FILA)->lockForUpdate()->first() ?? new TextosDePortada;
            $textos->forceFill(['id' => TextosDePortada::FILA, ...$limpios])->save();

            return $textos;
        });
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private function nosotros(array $datos): string
    {
        $texto = trim(is_string($datos['nosotros'] ?? null) ? $datos['nosotros'] : '');
        throw_if($texto === '' || mb_strlen($texto) > 2000, ReglaDeNegocioIncumplida::class, 'Completa la presentación del centro, hasta 2000 caracteres.');

        return $texto;
    }

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, list<array<string, string>>>
     */
    private function listas(array $datos): array
    {
        $limpias = [];

        foreach (self::LISTAS as $lista => [$nombre, $campos]) {
            $filas = is_array($datos[$lista] ?? null) ? array_values($datos[$lista]) : [];
            throw_if($filas === [] || count($filas) > self::MAXIMO_DE_FILAS, ReglaDeNegocioIncumplida::class, 'Registra de 1 a '.self::MAXIMO_DE_FILAS." filas en {$nombre}.");
            $limpias[$lista] = array_map(fn (mixed $fila): array => $this->fila($fila, $campos, $nombre), $filas);
        }

        return $limpias;
    }

    /**
     * @param  array<string, int>  $campos
     * @return array<string, string>
     */
    private function fila(mixed $fila, array $campos, string $nombre): array
    {
        $limpia = [];

        foreach ($campos as $campo => $maximo) {
            $texto = trim(is_array($fila) && is_string($fila[$campo] ?? null) ? $fila[$campo] : '');
            throw_if($texto === '' || mb_strlen($texto) > $maximo, ReglaDeNegocioIncumplida::class, "Completa cada fila de {$nombre}, hasta {$maximo} caracteres por campo.");
            $limpia[$campo] = $texto;
        }

        return $limpia;
    }
}
