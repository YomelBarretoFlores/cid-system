<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Data\PagoValidado;
use App\Modules\Pagos\Enums\TipoConcepto;

interface PagosValidadosDePersona
{
    /**
     * @return list<PagoValidado>
     */
    public function __invoke(int $personaId, TipoConcepto $servicio): array;
}
