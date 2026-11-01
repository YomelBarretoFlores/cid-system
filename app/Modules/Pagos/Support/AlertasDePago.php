<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Support;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Models\Pago;

// What the cashier must look at before validating: neither blocks a payment by itself.
final class AlertasDePago
{
    /**
     * @return list<string>
     */
    public static function de(Pago $pago): array
    {
        return array_values(array_filter([self::diferenciaDeMonto($pago), self::operacionEnOtroMedio($pago)]));
    }

    public static function diferenciaDeMonto(Pago $pago): ?string
    {
        $pagado = $pago->monto;
        $vigente = $pago->tarifa === null ? null : (string) $pago->tarifa->monto;

        if (! is_numeric($pagado) || ! is_numeric($vigente) || bccomp($pagado, $vigente, 2) === 0) {
            return null;
        }

        return "El monto pagado (S/ {$pagado}) no coincide con la tarifa (S/ {$vigente}).";
    }

    public static function operacionEnOtroMedio(Pago $pago): ?string
    {
        $otro = Pago::query()
            ->where('numero_operacion', $pago->numero_operacion)
            ->where('medio_pago', '<>', $pago->medio_pago)
            ->where('estado', '<>', EstadoPago::Rechazado)
            ->first();

        return $otro === null ? null : "El número de operación {$pago->numero_operacion} también figura en otro pago registrado con {$otro->medio_pago->etiqueta()}.";
    }
}
