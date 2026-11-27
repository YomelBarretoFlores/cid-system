<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

interface PagosAnterioresPermitidos
{
    /**
     * Whether the payment settings allow registering payments made before the system.
     */
    public function __invoke(): bool;
}
