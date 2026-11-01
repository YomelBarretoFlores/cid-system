<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Http\Resources;

use App\Modules\Pagos\Data\TarifaVigente;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property TarifaVigente $resource
 */
final class TarifaVigenteResource extends JsonResource
{
    /**
     * @return array{codigo: string, concepto: string, fuente: string, fuente_etiqueta: string, vinculo: string, vinculo_etiqueta: string, monto: string, vigente_desde: string}
     */
    public function toArray(Request $request): array
    {
        $tarifa = $this->resource;

        return [
            'codigo' => $tarifa->codigo,
            'concepto' => $tarifa->concepto,
            'fuente' => $tarifa->fuente,
            'fuente_etiqueta' => $tarifa->fuenteEtiqueta,
            'vinculo' => $tarifa->tipoVinculo->value,
            'vinculo_etiqueta' => $tarifa->tipoVinculo->etiqueta(),
            'monto' => $tarifa->monto,
            'vigente_desde' => $tarifa->vigenteDesde->toDateString(),
        ];
    }
}
