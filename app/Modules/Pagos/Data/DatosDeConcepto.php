<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use App\Modules\Pagos\Enums\TipoConcepto;

final readonly class DatosDeConcepto
{
    public function __construct(
        public string $nombre,
        public TipoConcepto $tipo,
        public int $fuenteId,
        public bool $activo,
    ) {}
}
