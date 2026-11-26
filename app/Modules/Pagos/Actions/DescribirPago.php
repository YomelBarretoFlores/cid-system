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
        $pago = Pago::query()->with(['concepto', 'medioDePago', 'tarifa'])->whereKey($pagoId)->firstOrFail();

        return new DetalleDePago(
            concepto: $pago->concepto->nombre,
            numeroOperacion: $pago->numero_operacion,
            medioPago: $pago->medioDePago->nombre,
            monto: (string) $pago->monto,
            fechaPago: CarbonImmutable::parse($pago->fecha_pago),
            estado: $pago->estado,
            personaId: $pago->persona_id,
            tipo: $pago->concepto->tipo,
            validadoEn: $pago->validado_en === null ? null : CarbonImmutable::parse($pago->validado_en)->setTimezone('America/Lima'),
            ulid: $pago->ulid,
            conceptoId: $pago->concepto_id,
            nivel: $pago->tarifa?->nivel,
            programaId: $pago->tarifa?->programa_id,
        );
    }
}
