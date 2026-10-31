<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ActaCerrada implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<int>  $matriculasAprobadas
     */
    public function __construct(
        public int $grupoId,
        public array $matriculasAprobadas,
    ) {}
}
