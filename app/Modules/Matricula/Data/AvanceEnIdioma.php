<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

use App\Modules\Matricula\Enums\EstadoMatricula;

final readonly class AvanceEnIdioma
{
    public function __construct(
        public string $idioma,
        public int $ciclo,
        public EstadoMatricula $estado,
        public string $periodo,
    ) {}
}
