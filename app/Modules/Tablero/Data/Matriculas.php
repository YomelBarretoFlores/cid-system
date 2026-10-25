<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use App\Modules\Tablero\Support\Porcentaje;

final readonly class Matriculas
{
    /**
     * @param  list<PuntoDeMatriculas>  $serie  up to twelve periods, oldest first, ending at the chosen one
     * @param  list<FilaDeMatriculas>  $porIdioma
     */
    public function __construct(
        public int $total,
        public ?PuntoDeMatriculas $anterior,
        public ?PuntoDeMatriculas $delAnioAnterior,
        public array $serie,
        public array $porIdioma,
    ) {}

    public function crecimientoMensual(): ?float
    {
        return Porcentaje::variacion($this->total, $this->anterior?->total);
    }

    public function crecimientoAnual(): ?float
    {
        return Porcentaje::variacion($this->total, $this->delAnioAnterior?->total);
    }
}
