<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Models\Modalidad;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class GuardarModalidad
{
    public const string SIN_LUGAR = 'La modalidad debe dictarse en aula, en línea o de ambas formas.';

    /**
     * Where a modality meets is fixed at creation: the groups already saved were checked against it.
     *
     * @param  array{nombre: string, descripcion: ?string, orden: int, activo: bool, usa_aula?: bool, usa_enlace?: bool}  $datos
     */
    public function __invoke(?int $modalidadId, array $datos): Modalidad
    {
        return Transaccion::ejecutar(function () use ($modalidadId, $datos): Modalidad {
            $modalidad = $modalidadId === null ? new Modalidad : Modalidad::query()->whereKey($modalidadId)->lockForUpdate()->firstOrFail();
            $comun = ['nombre' => trim($datos['nombre']), 'descripcion' => blank($datos['descripcion']) ? null : trim((string) $datos['descripcion']), 'orden' => $datos['orden'], 'activo' => $datos['activo']];

            if (! $modalidad->exists) {
                $usaAula = (bool) ($datos['usa_aula'] ?? false);
                $usaEnlace = (bool) ($datos['usa_enlace'] ?? false);
                throw_unless($usaAula || $usaEnlace, ReglaDeNegocioIncumplida::class, self::SIN_LUGAR);
                $comun = [...$comun, 'usa_aula' => $usaAula, 'usa_enlace' => $usaEnlace];
            }

            $modalidad->fill($comun)->save();

            return $modalidad;
        }, reintentar: false);
    }
}
