<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use Carbon\CarbonImmutable;

final readonly class SemanaDelSilabo
{
    public function __construct(
        public int $numero,
        public CarbonImmutable $desde,
        public CarbonImmutable $hasta,
        public ?string $unidad,
        public ?string $saber,
        public ?string $saberHacer,
        public ?string $saberSer,
        public ?string $actividades,
        public ?string $recursos,
    ) {}
}
