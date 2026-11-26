<?php

declare(strict_types=1);

namespace App\Modules\Personas\Data;

final readonly class TipoDeVinculo
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public bool $activo,
        public bool $porDefecto,
    ) {}
}
