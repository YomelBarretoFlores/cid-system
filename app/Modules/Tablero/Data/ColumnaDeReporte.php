<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use App\Modules\Tablero\Enums\TipoDeColumna;

final readonly class ColumnaDeReporte
{
    /**
     * @param  ?string  $grupo  heading shared by consecutive columns on the formal sheet (for example a program over its passed and failed columns)
     * @param  ?string  $corto  name under that shared heading; the full name is kept for the screen and Excel
     */
    public function __construct(
        public string $nombre,
        public TipoDeColumna $tipo = TipoDeColumna::Texto,
        public ?string $grupo = null,
        public ?string $corto = null,
    ) {}
}
