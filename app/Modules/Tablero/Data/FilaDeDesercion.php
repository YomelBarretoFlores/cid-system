<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

use App\Modules\Tablero\Support\Porcentaje;

final readonly class FilaDeDesercion
{
    public function __construct(
        public string $etiqueta,
        public int $matriculas,
        public int $retiradas,
    ) {}

    public function tasa(): ?float
    {
        return Porcentaje::de($this->retiradas, $this->matriculas);
    }
}
