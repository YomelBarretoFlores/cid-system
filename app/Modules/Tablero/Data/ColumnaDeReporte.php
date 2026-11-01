<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use App\Modules\Tablero\Enums\TipoDeColumna;

final readonly class ColumnaDeReporte
{
    public function __construct(
        public string $nombre,
        public TipoDeColumna $tipo = TipoDeColumna::Texto,
    ) {}
}
