<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

use Carbon\CarbonImmutable;

final readonly class RegistroDePago
{
    public function __construct(
        public int $personaId,
        public int $conceptoId,
        public int $medioPagoId,
        public string $numeroOperacion,
        public CarbonImmutable $fechaPago,
        public string $monto,
    ) {}
}
