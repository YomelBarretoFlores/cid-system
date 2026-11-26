<?php

declare(strict_types=1);

namespace App\Modules\Personas\Listeners;

use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Personas\Actions\HabilitarCuentaPorPago;

// Runs in the request, not the queue, so the student sees the full portal as soon as the cashier validates.
final readonly class AlValidarPago
{
    public function __construct(private HabilitarCuentaPorPago $habilitar) {}

    public function handle(PagoValidado $evento): void
    {
        ($this->habilitar)($evento->pagoId);
    }
}
