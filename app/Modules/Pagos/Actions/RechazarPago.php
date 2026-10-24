<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Events\PagoRechazado;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Database\Transaccion;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

final readonly class RechazarPago
{
    public function __invoke(int $pagoId, User $revisor, string $motivo): Pago
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new ReglaDeNegocioIncumplida('Indique el motivo del rechazo.');
        }

        return Transaccion::ejecutar(function () use ($pagoId, $revisor, $motivo): Pago {
            $pago = Pago::query()->whereKey($pagoId)->lockForUpdate()->firstOrFail();

            if ($pago->estado === EstadoPago::Rechazado) {
                return $pago;
            }

            $pago->estado->asegurarTransicionA(EstadoPago::Rechazado);
            $pago->update([
                'estado' => EstadoPago::Rechazado,
                'validado_por' => $revisor->id,
                'validado_en' => now(),
                'motivo_rechazo' => $motivo,
            ]);

            PagoRechazado::dispatch($pago->id);

            return $pago;
        });
    }
}
