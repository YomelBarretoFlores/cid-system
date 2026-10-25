<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Pagos\Data\DetalleDePago;
use App\Modules\Pagos\Models\Pago;
use Carbon\CarbonImmutable;

final readonly class DescribirPago implements ConsultaDePago
{
    public function __invoke(int $pagoId): DetalleDePago
    {
        $pago = Pago::query()->with('concepto')->whereKey($pagoId)->firstOrFail();

        return new DetalleDePago(
            concepto: $pago->concepto->nombre,
            numeroOperacion: $pago->numero_operacion,
            medioPago: $pago->medio_pago->etiqueta(),
            monto: (string) $pago->monto,
            fechaPago: CarbonImmutable::parse($pago->fecha_pago),
            estado: $pago->estado,
            personaId: $pago->persona_id,
            tipo: $pago->concepto->tipo,
        );
    }
}
