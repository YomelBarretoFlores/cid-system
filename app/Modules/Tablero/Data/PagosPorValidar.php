<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use App\Modules\Tablero\Support\Porcentaje;

final readonly class PagosPorValidar
{
    /**
     * @param  list<PagoPorValidar>  $masAntiguos
     */
    public function __construct(
        public int $registrados,
        public int $pendientes,
        public int $porValidar,
        public int $centimosPorValidar,
        public ?int $diasDelMasAntiguo,
        public array $masAntiguos,
    ) {}

    public function tasa(): ?float
    {
        return Porcentaje::de($this->pendientes, $this->registrados);
    }
}
