<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use Carbon\CarbonImmutable;

final readonly class PagoRechazado
{
    public function __construct(
        public string $concepto,
        public string $monto,
        public CarbonImmutable $fechaPago,
        public string $motivo,
    ) {}
}
