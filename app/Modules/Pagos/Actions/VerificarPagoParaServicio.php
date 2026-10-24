<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Contracts\VerificacionDePago;
use App\Modules\Pagos\Data\PagoData;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final readonly class VerificarPagoParaServicio implements VerificacionDePago
{
    public function __invoke(int $pagoId, int $personaId, TipoConcepto $servicio): PagoData
    {
        return DB::transaction(function () use ($pagoId, $personaId, $servicio): PagoData {
            $pago = Pago::query()->with('concepto')->whereKey($pagoId)->lockForUpdate()->firstOrFail();

            $error = match (true) {
                $pago->persona_id !== $personaId => 'El pago pertenece a otra persona.',
                $pago->estado !== EstadoPago::Validado => 'El pago no está validado.',
                $pago->concepto->tipo !== $servicio => 'El pago no corresponde a este servicio.',
                default => null,
            };

            if ($error !== null) {
                throw new ReglaDeNegocioIncumplida($error);
            }

            return new PagoData($pago->id, $pago->persona_id, $servicio);
        });
    }
}
