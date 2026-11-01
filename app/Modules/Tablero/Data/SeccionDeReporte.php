<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

final readonly class SeccionDeReporte
{
    /**
     * @param  list<ColumnaDeReporte>  $columnas
     * @param  list<list<string|int|float|null>>  $filas  one value per column, numbers as numbers
     */
    public function __construct(
        public string $titulo,
        public array $columnas,
        public array $filas,
    ) {}
}
