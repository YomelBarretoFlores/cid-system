<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Data\PagoData;
use App\Modules\Pagos\Enums\TipoConcepto;

interface VerificacionDePago
{
    /** Locks the payment row until the caller's transaction ends. */
    public function __invoke(int $pagoId, int $personaId, TipoConcepto $servicio): PagoData;
}
