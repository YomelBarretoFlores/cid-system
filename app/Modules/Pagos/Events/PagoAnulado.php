<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Events;

use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

final readonly class PagoAnulado implements ShouldDispatchAfterCommit
{
    use Dispatchable;

    public function __construct(public int $pagoId) {}
}
