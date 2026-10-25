<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

final readonly class RentabilidadPorGrupo
{
    /**
     * @param  list<FilaDeRentabilidad>  $filas
     */
    public function __construct(
        public array $filas,
        public int $ingresosCentimos,
        public int $costoCentimos,
        public int $horasSinCostoCentesimas,
    ) {}

    public function margen(): int
    {
        return $this->ingresosCentimos - $this->costoCentimos;
    }
}
