<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use Carbon\CarbonImmutable;

final readonly class DetalleDePago
{
    public function __construct(
        public string $concepto,
        public string $numeroOperacion,
        public string $medioPago,
        public string $monto,
        public CarbonImmutable $fechaPago,
        public EstadoPago $estado,
        public int $personaId,
        public TipoConcepto $tipo,
        public ?CarbonImmutable $validadoEn = null,
        public ?string $ulid = null,
        public ?int $conceptoId = null,
        public ?NivelCurricular $nivel = null,
        public ?int $programaId = null,
    ) {}
}
