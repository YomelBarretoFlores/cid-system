<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

final readonly class DocenteAsignado
{
    public function __construct(
        public int $docenteId,
        public string $nombreCompleto,
    ) {}
}
