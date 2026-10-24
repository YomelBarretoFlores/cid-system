<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use App\Modules\Pagos\Enums\MedioPago;
use Carbon\CarbonImmutable;

final readonly class RegistroDePago
{
    public function __construct(
        public int $personaId,
        public int $conceptoId,
        public MedioPago $medioPago,
        public string $numeroOperacion,
        public CarbonImmutable $fechaPago,
        public string $monto,
    ) {}
}
