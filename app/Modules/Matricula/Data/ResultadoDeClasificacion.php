<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

use Carbon\CarbonImmutable;

final readonly class ResultadoDeClasificacion
{
    public function __construct(
        public CarbonImmutable $fechaExamen,
        public string $puntaje,
        public int $cicloId,
        public ?string $observaciones,
    ) {}
}
