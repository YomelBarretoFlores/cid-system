<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Personas\Enums\TipoVinculo;
use Carbon\CarbonImmutable;

final readonly class TarifaVigente
{
    public function __construct(
        public string $codigo,
        public string $concepto,
        public string $fuente,
        public string $fuenteEtiqueta,
        public TipoVinculo $tipoVinculo,
        public string $monto,
        public CarbonImmutable $vigenteDesde,
        public TipoConcepto $tipo,
    ) {}
}
