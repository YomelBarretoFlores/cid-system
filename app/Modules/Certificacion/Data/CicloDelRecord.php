<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Data;

final readonly class CicloDelRecord
{
    public function __construct(
        public NotaDelHistorial $resultado,
        public int $veces,
    ) {}
}
