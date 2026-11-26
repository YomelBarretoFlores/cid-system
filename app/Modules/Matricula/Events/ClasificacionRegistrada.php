<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

// Also dispatched when a correction moves the assigned cycle.
final readonly class ClasificacionRegistrada implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  string  $fechaExamen  Y-m-d in Lima
     */
    public function __construct(
        public int $solicitudId,
        public int $personaId,
        public int $cicloId,
        public string $fechaExamen,
        public int $usuarioId,
    ) {}
}
