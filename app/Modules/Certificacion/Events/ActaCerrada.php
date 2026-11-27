<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class ActaCerrada implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  list<int>  $matriculasAprobadas  passed every cycle of the group
     * @param  list<int>  $matriculasConSoloElPrimerCiclo  passed the first cycle of an accelerated group but not its second
     */
    public function __construct(
        public int $grupoId,
        public array $matriculasAprobadas,
        public array $matriculasConSoloElPrimerCiclo = [],
    ) {}
}
