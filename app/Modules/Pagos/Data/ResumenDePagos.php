<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Data;

final readonly class ResumenDePagos
{
    /**
     * @param  ?PagoRechazado  $ultimoRechazo  only when the newest payment is the rejected one
     * @param  ?string  $montoDeMatricula  current enrollment fee for the person's link
     */
    public function __construct(
        public int $porValidar,
        public int $validados,
        public bool $matriculaPorValidar,
        public ?PagoRechazado $ultimoRechazo,
        public ?string $montoDeMatricula,
    ) {}
}
