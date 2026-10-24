<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

final readonly class FilaDeConcepto
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public string $fuente,
        public int $centimos,
        public int $operaciones,
    ) {}
}
