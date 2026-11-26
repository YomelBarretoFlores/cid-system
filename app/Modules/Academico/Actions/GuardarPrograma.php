<?php

declare(strict_types=1);

namespace App\Modules\Academico\Actions;

use App\Modules\Academico\Models\Programa;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class GuardarPrograma
{
    public const string POR_DEFECTO_INACTIVO = 'El programa por defecto debe estar disponible para nuevos grupos.';

    public const string SIN_POR_DEFECTO = 'Marque otro programa por defecto antes de quitar este: es el que se propone para grupos nuevos y el que se imprime en los documentos de grupos anteriores a los programas.';

    /**
     * @param  array{codigo?: string, nombre: string, regimen: string, activo: bool, por_defecto: bool}  $datos  the code is taken only on creation
     */
    public function __invoke(?int $programaId, array $datos): Programa
    {
        if ($datos['por_defecto'] && ! $datos['activo']) {
            throw new ReglaDeNegocioIncumplida(self::POR_DEFECTO_INACTIVO);
        }

        return Transaccion::ejecutar(fn (): Programa => $this->guardar($programaId, $datos), reintentar: false);
    }

    /**
     * @param  array{codigo?: string, nombre: string, regimen: string, activo: bool, por_defecto: bool}  $datos
     */
    private function guardar(?int $programaId, array $datos): Programa
    {
        // Locking the current default serializes every change of which program is the default.
        $actual = Programa::query()->where('por_defecto', true)->lockForUpdate()->first();
        $programa = $programaId === null ? new Programa : Programa::query()->whereKey($programaId)->lockForUpdate()->firstOrFail();
        $esElActual = $actual?->is($programa) ?? false;

        if ($esElActual && ! $datos['por_defecto']) {
            throw new ReglaDeNegocioIncumplida(self::SIN_POR_DEFECTO);
        }

        if ($datos['por_defecto'] && ! $esElActual) {
            $actual?->update(['por_defecto' => false]);
        }

        $campos = ['nombre' => $datos['nombre'], 'regimen' => $datos['regimen'], 'activo' => $datos['activo'], 'por_defecto' => $datos['por_defecto']];
        $programa->fill($programa->exists ? $campos : [...$campos, 'codigo' => $datos['codigo'] ?? ''])->save();

        return $programa;
    }
}
