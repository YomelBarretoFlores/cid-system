<?php

declare(strict_types=1);

namespace App\Modules\Personas\Actions;

use App\Modules\Personas\Models\TipoDocumento;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class GuardarTipoDeDocumento
{
    public const int LONGITUD_TOPE = 20;

    public const string LONGITUD_INVALIDA = 'La longitud mínima debe ser de al menos 1 y no mayor que la máxima, y la máxima no puede pasar de 20 caracteres.';

    public const string POR_DEFECTO_INACTIVO = 'El tipo de documento por defecto debe estar disponible en los formularios.';

    public const string SIN_POR_DEFECTO = 'Marque otro tipo de documento por defecto antes de quitar este: es el que se preselecciona al crear una cuenta en el portal.';

    /**
     * Rules apply to new or edited records only: a stored number stays valid when its rule changes later.
     *
     * @param  array{nombre: string, solo_digitos: bool, longitud_minima: int, longitud_maxima: int, orden: int, activo: bool, por_defecto: bool}  $datos
     */
    public function __invoke(?int $tipoId, array $datos): TipoDocumento
    {
        if ($datos['longitud_minima'] < 1 || $datos['longitud_minima'] > $datos['longitud_maxima'] || $datos['longitud_maxima'] > self::LONGITUD_TOPE) {
            throw new ReglaDeNegocioIncumplida(self::LONGITUD_INVALIDA);
        }

        if ($datos['por_defecto'] && ! $datos['activo']) {
            throw new ReglaDeNegocioIncumplida(self::POR_DEFECTO_INACTIVO);
        }

        return Transaccion::ejecutar(fn (): TipoDocumento => $this->guardar($tipoId, $datos), reintentar: false);
    }

    /**
     * @param  array{nombre: string, solo_digitos: bool, longitud_minima: int, longitud_maxima: int, orden: int, activo: bool, por_defecto: bool}  $datos
     */
    private function guardar(?int $tipoId, array $datos): TipoDocumento
    {
        // Locking the current default serializes every change of which type is the default.
        $actual = TipoDocumento::query()->where('por_defecto', true)->lockForUpdate()->first();
        $tipo = $tipoId === null ? new TipoDocumento : TipoDocumento::query()->whereKey($tipoId)->lockForUpdate()->firstOrFail();
        $esElActual = $actual?->is($tipo) ?? false;

        if ($esElActual && ! $datos['por_defecto']) {
            throw new ReglaDeNegocioIncumplida(self::SIN_POR_DEFECTO);
        }

        if ($datos['por_defecto'] && ! $esElActual) {
            $actual?->update(['por_defecto' => false]);
        }

        $tipo->fill($datos)->save();

        return $tipo;
    }
}
