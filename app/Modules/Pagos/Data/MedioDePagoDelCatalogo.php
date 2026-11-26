<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

final readonly class MedioDePagoDelCatalogo
{
    public function __construct(
        public string $codigo,
        public string $nombre,
    ) {}
}
