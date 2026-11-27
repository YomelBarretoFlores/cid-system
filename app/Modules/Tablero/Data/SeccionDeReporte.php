<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

final readonly class SeccionDeReporte
{
    /**
     * @param  list<ColumnaDeReporte>  $columnas
     * @param  list<list<string|int|float|null>>  $filas  one value per column, numbers as numbers
     * @param  list<list<string|int|float|null>>  $totales  subtotal and total rows, printed in bold after the rows
     * @param  ?string  $encabezado  heading printed on the formal sheet instead of the title
     */
    public function __construct(
        public string $titulo,
        public array $columnas,
        public array $filas,
        public array $totales = [],
        public ?string $encabezado = null,
    ) {}
}
