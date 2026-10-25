<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use App\Modules\Tablero\Support\Porcentaje;

final readonly class FranjaHoraria
{
    /**
     * @param  list<int>  $dias  ISO weekdays, 1 = Monday
     */
    public function __construct(
        public string $horaInicio,
        public string $horaFin,
        public array $dias,
        public int $grupos,
        public int $matriculados,
        public int $cupo,
    ) {}

    public function ocupacion(): ?float
    {
        return Porcentaje::de($this->matriculados, $this->cupo);
    }
}
