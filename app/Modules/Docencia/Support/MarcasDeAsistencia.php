<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Support;

use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final class MarcasDeAsistencia
{
    /**
     * @param  array<int, mixed>  $estados  state per enrollment id
     * @param  array<int, int>  $matriculas  enrollments the call must cover, no more and no less
     * @param  list<EstadoAsistencia>  $permitidos
     * @return array<int, EstadoAsistencia>
     */
    public static function validar(array $estados, array $matriculas, array $permitidos): array
    {
        if (array_diff(array_keys($estados), $matriculas) !== []) {
            throw new ReglaDeNegocioIncumplida('La lista de estudiantes del grupo cambió; vuelve a cargarla.');
        }

        if (array_diff($matriculas, array_keys($estados)) !== []) {
            throw new ReglaDeNegocioIncumplida('Marca la asistencia de todos los estudiantes del grupo.');
        }

        return array_map(fn (mixed $estado): EstadoAsistencia => self::estado($estado, $permitidos), $estados);
    }

    /**
     * @param  list<EstadoAsistencia>  $permitidos
     */
    private static function estado(mixed $estado, array $permitidos): EstadoAsistencia
    {
        $valor = is_string($estado) ? EstadoAsistencia::tryFrom($estado) : null;

        return $valor !== null && in_array($valor, $permitidos, true) ? $valor : throw new ReglaDeNegocioIncumplida('El estado de asistencia no es válido.');
    }
}
