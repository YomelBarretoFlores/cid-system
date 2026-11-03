<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Support;

use App\Modules\Matricula\Contracts\ContextoAcademicoDePagos;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Tramites\Contracts\TramiteDelPago;

// What the cashier must look at before validating or voiding: none blocks a payment by itself.
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
            ->where('medio_pago_id', '<>', $pago->medio_pago_id)
            ->where('estado', '<>', EstadoPago::Rechazado)
            ->first();

        return $otro === null ? null : "El número de operación {$pago->numero_operacion} también figura en otro pago registrado con {$otro->medioDePago->nombre}.";
    }

    // What a void leaves without its payment: an enrollment, a placement request or a procedure.
    public static function respaldo(Pago $pago): ?string
    {
        $contexto = app(ContextoAcademicoDePagos::class)([$pago->id])[$pago->id] ?? null;

        if ($contexto !== null) {
            return $contexto->periodo === null
                ? "Este pago respalda la solicitud de examen de clasificación de {$contexto->idioma}."
                : "Este pago respalda la matrícula en {$contexto->idioma} {$contexto->nivel} ({$contexto->periodo}).";
        }

        $tramite = app(TramiteDelPago::class)($pago->id);

        return $tramite === null ? null : "Este pago respalda el trámite {$tramite}.";
    }
}
