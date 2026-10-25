<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

// SVG paths for the growth chart, drawn in a 560 × 200 box with room above and below the line.
final class GraficoDeLinea
{
    public const int ANCHO = 560;

    public const int ALTO = 200;

    private const int MARGEN = 20;

    /**
     * @param  list<int>  $valores
     * @return array{linea: string, area: string, puntos: list<array{x: float, y: float}>}
     */
    public static function trazos(array $valores): array
    {
        if ($valores === []) {
            return ['linea' => '', 'area' => '', 'puntos' => []];
        }

        $maximo = max(1, ...$valores);
        $paso = count($valores) === 1 ? 0 : self::ANCHO / (count($valores) - 1);
        $puntos = array_map(fn (int $valor, int $indice): array => [
            'x' => round(count($valores) === 1 ? self::ANCHO / 2 : $indice * $paso, 1),
            'y' => round(self::ALTO - self::MARGEN - $valor / $maximo * (self::ALTO - 2 * self::MARGEN), 1),
        ], $valores, array_keys($valores));
        $linea = 'M'.implode(' L', array_map(fn (array $punto): string => "{$punto['x']} {$punto['y']}", $puntos));
        $base = self::ALTO;

        return [
            'linea' => $linea,
            'area' => "{$linea} L{$puntos[count($puntos) - 1]['x']} {$base} L{$puntos[0]['x']} {$base} Z",
            'puntos' => $puntos,
        ];
    }
}
