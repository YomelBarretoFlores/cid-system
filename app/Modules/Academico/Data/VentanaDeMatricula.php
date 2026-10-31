<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use Carbon\CarbonImmutable;

final readonly class VentanaDeMatricula
{
    public function __construct(
        public int $periodoId,
        public string $periodo,
        public CarbonImmutable $inicio,
        public CarbonImmutable $fin,
        public bool $abierta,
    ) {}
}
