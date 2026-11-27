<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Listeners;

use App\Modules\Certificacion\Events\ActaCerrada;
use App\Modules\Matricula\Actions\HabilitarCiclosSiguientes;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\Attributes\Backoff;
use Illuminate\Queue\Attributes\Tries;

#[Backoff([10, 60])]
#[Tries(3)]
final readonly class AlCerrarActa implements ShouldQueue
{
    public function __construct(private HabilitarCiclosSiguientes $accion) {}

    public function handle(ActaCerrada $evento): void
    {
        ($this->accion)($evento->matriculasAprobadas, $evento->matriculasConSoloElPrimerCiclo);
    }
}
