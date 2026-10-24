<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use Carbon\CarbonImmutable;

final readonly class PagoValidado
{
    public function __construct(
        public int $id,
        public string $concepto,
        public string $numeroOperacion,
        public string $monto,
        public CarbonImmutable $fechaPago,
    ) {}
}
