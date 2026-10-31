<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Contracts;

use App\Modules\Pagos\Data\ResumenDePagos;

interface ResumenDePagosDePersona
{
    public function __invoke(int $personaId): ResumenDePagos;
}
