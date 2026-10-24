<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Data\DetalleDePago;

interface ConsultaDePago
{
    public function __invoke(int $pagoId): DetalleDePago;
}
