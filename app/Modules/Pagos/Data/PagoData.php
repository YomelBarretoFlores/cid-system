<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use App\Modules\Pagos\Enums\TipoConcepto;

final readonly class PagoData
{
    public function __construct(
        public int $id,
        public int $personaId,
        public TipoConcepto $servicio,
    ) {}
}
