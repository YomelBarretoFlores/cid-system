<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

final readonly class PuntoDeMatriculas
{
    public function __construct(
        public int $periodoId,
        public string $periodo,
        public int $total,
    ) {}
}
