<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

// The presentation and the step, level and question lists, cleaned the same way for saving and for the preview.
final class LimpiezaDeListasDePortada
{
    private const int MAXIMO_DE_FILAS = 12;

    /** @var array<string, array{string, array<string, int>}> list => [name, field => max length] */
    private const array LISTAS = [
        'pasos_matricula' => ['los pasos de la matrícula', ['titulo' => 80, 'detalle' => 300]],
        'pasos_clasificacion' => ['los pasos del examen de clasificación', ['titulo' => 80, 'detalle' => 300]],
        'niveles' => ['los niveles', ['nombre' => 40, 'descripcion' => 300, 'logro' => 300]],
        'preguntas' => ['las preguntas frecuentes', ['pregunta' => 150, 'respuesta' => 600]],
    ];

    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, string|list<array<string, string>>>
     */
    public static function de(array $datos): array
    {
        $limpias = ['nosotros' => self::nosotros($datos)];

        foreach (self::LISTAS as $lista => [$nombre, $campos]) {
            $filas = is_array($datos[$lista] ?? null) ? array_values($datos[$lista]) : [];
            throw_if($filas === [] || count($filas) > self::MAXIMO_DE_FILAS, ReglaDeNegocioIncumplida::class, 'Registra de 1 a '.self::MAXIMO_DE_FILAS." filas en {$nombre}.");
            $limpias[$lista] = array_map(fn (mixed $fila): array => self::fila($fila, $campos, $nombre), $filas);
        }

        return $limpias;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    private static function nosotros(array $datos): string
    {
        $texto = trim(is_string($datos['nosotros'] ?? null) ? $datos['nosotros'] : '');
        throw_if($texto === '' || mb_strlen($texto) > 2000, ReglaDeNegocioIncumplida::class, 'Completa la presentación del centro, hasta 2000 caracteres.');

        return $texto;
    }

    /**
     * @param  array<string, int>  $campos
     * @return array<string, string>
     */
    private static function fila(mixed $fila, array $campos, string $nombre): array
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
