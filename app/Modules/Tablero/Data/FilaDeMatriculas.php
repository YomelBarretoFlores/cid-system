<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Data;

final readonly class FilaDeMatriculas
{
    public function __construct(
        public string $idioma,
        public int $total,
    ) {}
}
