<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class AvanceEnIdioma
{
    public function __construct(
        public string $idioma,
        public int $ciclo,
        public bool $activa,
        public string $periodo,
    ) {}
}
