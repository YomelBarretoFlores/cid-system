<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

final readonly class Recaudacion
{
    /**
     * @param  list<FilaDeRecaudacion>  $filas
     */
    public function __construct(
        public int $totalCentimos,
        public int $operaciones,
        public array $filas,
    ) {}
}
