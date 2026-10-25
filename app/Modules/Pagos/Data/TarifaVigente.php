<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use App\Modules\Pagos\Enums\FuenteConcepto;
use App\Modules\Personas\Enums\TipoVinculo;
use Carbon\CarbonImmutable;

final readonly class TarifaVigente
{
    public function __construct(
        public string $codigo,
        public string $concepto,
        public FuenteConcepto $fuente,
        public TipoVinculo $tipoVinculo,
        public string $monto,
        public CarbonImmutable $vigenteDesde,
    ) {}
}
