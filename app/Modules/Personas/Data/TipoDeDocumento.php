<?php

declare(strict_types=1);

namespace App\Modules\Personas\Data;

final readonly class TipoDeDocumento
{
    public function __construct(
        public string $codigo,
        public string $nombre,
        public bool $soloDigitos,
        public int $longitudMinima,
        public int $longitudMaxima,
        public bool $activo,
        public bool $porDefecto,
    ) {}
}
