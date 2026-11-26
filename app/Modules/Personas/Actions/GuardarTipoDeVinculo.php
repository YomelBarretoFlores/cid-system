<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Models\TipoVinculo;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class GuardarTipoDeVinculo
{
    public const string POR_DEFECTO_INACTIVO = 'El vínculo por defecto debe estar disponible en los formularios.';

    public const string SIN_POR_DEFECTO = 'Marque otro vínculo por defecto antes de quitar este: es el que recibe quien crea su cuenta en el portal.';

    /**
     * @param  array{nombre: string, orden: int, activo: bool, por_defecto: bool}  $datos
     */
    public function __invoke(?int $vinculoId, array $datos): TipoVinculo
    {
        if ($datos['por_defecto'] && ! $datos['activo']) {
            throw new ReglaDeNegocioIncumplida(self::POR_DEFECTO_INACTIVO);
        }

        return Transaccion::ejecutar(fn (): TipoVinculo => $this->guardar($vinculoId, $datos), reintentar: false);
    }

    /**
     * @param  array{nombre: string, orden: int, activo: bool, por_defecto: bool}  $datos
     */
    private function guardar(?int $vinculoId, array $datos): TipoVinculo
    {
        // Locking the current default serializes every change of which vínculo is the default.
        $actual = TipoVinculo::query()->where('por_defecto', true)->lockForUpdate()->first();
        $vinculo = $vinculoId === null ? new TipoVinculo : TipoVinculo::query()->whereKey($vinculoId)->lockForUpdate()->firstOrFail();
        $esElActual = $actual?->is($vinculo) ?? false;

        if ($esElActual && ! $datos['por_defecto']) {
            throw new ReglaDeNegocioIncumplida(self::SIN_POR_DEFECTO);
        }

        if ($datos['por_defecto'] && ! $esElActual) {
            $actual?->update(['por_defecto' => false]);
        }

        $vinculo->fill($datos)->save();

        return $vinculo;
    }
}
