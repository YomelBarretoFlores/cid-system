<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Models\GradoFut;
use App\Shared\Support\Database\Transaccion;

final readonly class GuardarGradoFut
{
    /**
     * @param  array{nombre: string, titulo_otorgado: string, orden: int, activo: bool}  $datos
     */
    public function __invoke(?int $gradoId, array $datos): GradoFut
    {
        return Transaccion::ejecutar(function () use ($gradoId, $datos): GradoFut {
            $grado = $gradoId === null ? new GradoFut : GradoFut::query()->whereKey($gradoId)->lockForUpdate()->firstOrFail();
            $grado->fill([...$datos, 'nombre' => trim($datos['nombre']), 'titulo_otorgado' => trim($datos['titulo_otorgado'])])->save();

            return $grado;
        }, reintentar: false);
    }
}
