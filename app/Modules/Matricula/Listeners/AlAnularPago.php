<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Listeners;

use App\Modules\Matricula\Actions\AnularSolicitudPorPagoAnulado;
use App\Modules\Pagos\Events\PagoAnulado;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;

#[Backoff([10, 60])]
#[Tries(3)]
final readonly class AlAnularPago implements ShouldQueue
{
    public function __construct(private AnularSolicitudPorPagoAnulado $accion) {}

    public function handle(PagoAnulado $evento): void
    {
        ($this->accion)($evento->pagoId);
    }
}
