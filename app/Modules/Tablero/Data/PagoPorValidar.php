<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use Carbon\CarbonImmutable;

final readonly class PagoPorValidar
{
    public function __construct(
        public int $pagoId,
        public string $pagoUlid,
        public string $concepto,
        public int $centimos,
        public CarbonImmutable $fechaPago,
        public int $dias,
    ) {}
}
