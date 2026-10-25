<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Http\Resources;

use App\Modules\Pagos\Models\Pago;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Pago $resource
 */
final class PagoDelEstudianteResource extends JsonResource
{
    /**
     * @return array{id: string, concepto: string, monto: string, fecha_pago: string, medio_pago: string, medio_pago_etiqueta: string, numero_operacion: string, estado: string, estado_etiqueta: string, motivo_rechazo: ?string}
     */
    public function toArray(Request $request): array
    {
        $pago = $this->resource;

        return [
            'id' => $pago->ulid,
            'concepto' => $pago->concepto->nombre,
            'monto' => $pago->monto,
            'fecha_pago' => $pago->fecha_pago->toDateString(),
            'medio_pago' => $pago->medio_pago->value,
            'medio_pago_etiqueta' => $pago->medio_pago->etiqueta(),
            'numero_operacion' => $pago->numero_operacion,
            'estado' => $pago->estado->value,
            'estado_etiqueta' => $pago->estado->etiqueta(),
            'motivo_rechazo' => $pago->motivo_rechazo,
        ];
    }
}
