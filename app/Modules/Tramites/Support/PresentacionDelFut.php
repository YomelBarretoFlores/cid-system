<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Tramites\Enums\CampoFut;
use App\Modules\Tramites\Models\GradoFut;

// Shows the stored request by the FUT labels, whatever the type asks today.
final class PresentacionDelFut
{
    /**
     * @param  array<string, mixed>  $datos
     * @return array<string, string> label => value, in form order
     */
    public static function filas(array $datos): array
    {
        $filas = [];

        foreach (CampoFut::cases() as $campo) {
            $valor = self::valor($campo, $datos);

            if ($valor !== null) {
                $filas[$campo->etiqueta()] = $valor;
            }
        }

        return $filas;
    }

    /**
     * @param  array<string, mixed>  $datos
     */
    public static function valor(CampoFut $campo, array $datos): ?string
    {
        $texto = fn (string $clave): ?string => is_scalar($datos[$clave] ?? null) ? (string) $datos[$clave] : null;

        return match ($campo) {
            CampoFut::Nivel => NivelCurricular::tryFrom((string) $texto('nivel'))?->etiqueta() ?? $texto('nivel'),
            CampoFut::Mencion => self::mencion($texto('mencion_grado'), $texto('mencion_grado_nombre'), $texto('mencion')),
            default => $texto($campo->value),
        };
    }

    /**
     * The degree the frozen grade grants with its specialty ("Maestro en Educación"), or null for requests made before it was frozen.
     *
     * @param  array<string, mixed>  $datos
     */
    public static function gradoOtorgado(array $datos): ?string
    {
        $titulo = $datos['mencion_grado_titulo'] ?? null;
        $especialidad = $datos['mencion'] ?? null;

        if (! is_string($titulo)) {
            return null;
        }

        return is_string($especialidad) ? "{$titulo} en {$especialidad}" : $titulo;
    }

    // Requests made before the name was frozen read it from the catalog.
    private static function mencion(?string $grado, ?string $congelado, ?string $especialidad): ?string
    {
        if ($grado === null) {
            return null;
        }

        $nombre = $congelado ?? GradoFut::query()->where('codigo', $grado)->value('nombre') ?? $grado;

        return $especialidad === null ? $nombre : "{$nombre} en {$especialidad}";
    }
}
