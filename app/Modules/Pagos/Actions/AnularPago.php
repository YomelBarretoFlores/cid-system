<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Events\PagoAnulado;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Support\Facades\DB;

final readonly class AnularPago
{
    public function __invoke(int $pagoId, User $usuario, string $motivo): Pago
    {
        $motivo = trim($motivo);

        if ($motivo === '') {
            throw new ReglaDeNegocioIncumplida('Indique el motivo de la anulación.');
        }

        return DB::transaction(function () use ($pagoId, $usuario, $motivo): Pago {
            $pago = Pago::query()->whereKey($pagoId)->lockForUpdate()->firstOrFail();

            if ($pago->estado === EstadoPago::Anulado) {
                return $pago;
            }

            $pago->estado->asegurarTransicionA(EstadoPago::Anulado);
            $pago->update([
                'estado' => EstadoPago::Anulado,
                'anulado_por' => $usuario->id,
                'anulado_en' => now(),
                'motivo_anulacion' => $motivo,
            ]);

            PagoAnulado::dispatch($pago->id);

            return $pago;
        });
    }
}
