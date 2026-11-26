<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Enums\TipoConcepto;

final readonly class PagoData
{
    public function __construct(
        public int $id,
        public int $personaId,
        public TipoConcepto $servicio,
        public ?int $conceptoId = null,
        public ?NivelCurricular $nivel = null,
        public ?int $programaId = null,
    ) {}
}
