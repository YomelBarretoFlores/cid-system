<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use App\Modules\Pagos\Enums\TipoConcepto;

final readonly class ConceptoDelCatalogo
{
    public function __construct(
        public int $id,
        public string $codigo,
        public string $nombre,
        public TipoConcepto $tipo,
        public bool $activo,
    ) {}
}
