<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use Carbon\CarbonImmutable;

final readonly class FiltrosDeRecaudacion
{
    public function __construct(
        public CarbonImmutable $desde,
        public CarbonImmutable $hasta,
        public ?int $conceptoId = null,
        public ?int $periodoId = null,
    ) {}
}
