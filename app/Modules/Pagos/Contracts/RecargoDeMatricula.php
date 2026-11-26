<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use Carbon\CarbonInterface;

interface RecargoDeMatricula
{
    /** The surcharge percentage an enrollment paid on that date carries, or null when none applies. */
    public function __invoke(CarbonInterface $fecha): ?int;
}
