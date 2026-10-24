<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Auth\Models\User;
use Illuminate\Support\Facades\DB;

final readonly class ValidarPago
{
    public function __invoke(int $pagoId, User $validador): Pago
    {
        return DB::transaction(function () use ($pagoId, $validador): Pago {
            $pago = Pago::query()->whereKey($pagoId)->lockForUpdate()->firstOrFail();

            if ($pago->estado === EstadoPago::Validado) {
                return $pago;
            }

            $pago->estado->asegurarTransicionA(EstadoPago::Validado);
            $pago->update([
                'estado' => EstadoPago::Validado,
                'validado_por' => $validador->id,
                'validado_en' => now(),
            ]);

            PagoValidado::dispatch($pago->id);

            return $pago;
        });
    }
}
