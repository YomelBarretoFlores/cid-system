<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Support;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Tramites\Enums\CampoFut;

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
            CampoFut::Mencion => self::mencion($texto('mencion_grado'), $texto('mencion')),
            default => $texto($campo->value),
        };
    }

    private static function mencion(?string $grado, ?string $especialidad): ?string
    {
        if ($grado === null) {
            return null;
        }

        $nombre = LecturaDelFut::GRADOS[$grado] ?? $grado;

        return $especialidad === null ? $nombre : "{$nombre} en {$especialidad}";
    }
}
