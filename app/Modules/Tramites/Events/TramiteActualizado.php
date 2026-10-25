<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class TramiteActualizado implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $tramiteId, public int $movimientoId) {}
}
