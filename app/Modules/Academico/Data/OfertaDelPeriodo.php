<?php

declare(strict_types=1);

namespace App\Modules\Academico\Data;

use Carbon\CarbonImmutable;

final readonly class OfertaDelPeriodo
{
    /**
     * @param  list<GrupoOfertado>  $grupos
     */
    public function __construct(
        public string $periodo,
        public bool $matriculaAbierta,
        public CarbonImmutable $matriculaInicio,
        public CarbonImmutable $matriculaFin,
        public CarbonImmutable $clasesInicio,
        public CarbonImmutable $clasesFin,
        public array $grupos,
    ) {}
}
