<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Listeners;

use App\Modules\Pagos\Events\PagoValidado;
use App\Modules\Tramites\Actions\AplicarPagoValidado;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;

#[Backoff([10, 60])]
#[Tries(3)]
final readonly class AlValidarPago implements ShouldQueue
{
    public function __construct(private AplicarPagoValidado $accion) {}

    public function handle(PagoValidado $evento): void
    {
        // A business refusal will not change on retry, so it is logged instead of failing the job.
        try {
            ($this->accion)($evento->pagoId);
        } catch (ReglaDeNegocioIncumplida $error) {
            RegistroDeRechazos::registrar($error);
        }
    }
}
