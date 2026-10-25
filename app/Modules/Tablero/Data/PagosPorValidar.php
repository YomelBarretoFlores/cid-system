<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

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
        return $this->registrados === 0 ? null : round($this->pendientes * 100 / $this->registrados, 1);
    }
}
