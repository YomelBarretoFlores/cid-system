<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

final readonly class FilaDeRecaudacion
{
    public function __construct(
        public string $idioma,
        public string $nivel,
        public int $operaciones,
        public int $centimos,
    ) {}
}
