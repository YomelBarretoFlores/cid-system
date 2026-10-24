<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use Carbon\CarbonImmutable;

final readonly class DetalleDeRecaudacion
{
    public function __construct(
        public CarbonImmutable $fechaPago,
        public string $documento,
        public string $estudiante,
        public string $concepto,
        public string $idioma,
        public string $nivel,
        public string $periodo,
        public string $medioPago,
        public string $numeroOperacion,
        public int $centimos,
    ) {}
}
