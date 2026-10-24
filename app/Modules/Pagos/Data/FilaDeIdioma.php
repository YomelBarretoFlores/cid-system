<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

final readonly class FilaDeIdioma
{
    public function __construct(
        public string $idioma,
        public string $nivel,
        public int $centimos,
        public int $operaciones,
    ) {}
}
