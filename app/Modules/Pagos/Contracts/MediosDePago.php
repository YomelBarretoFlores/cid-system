<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Data\MedioDePagoDelCatalogo;

interface MediosDePago
{
    /**
     * @return list<MedioDePagoDelCatalogo> the active payment channels, in the catalog order
     */
    public function __invoke(): array;
}
