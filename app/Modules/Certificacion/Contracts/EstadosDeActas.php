<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Contracts;

use App\Modules\Certificacion\Enums\EstadoActa;

interface EstadosDeActas
{
    /**
     * @param  list<int>  $grupoIds
     * @return array<int, EstadoActa> group id => state of its acta, only for groups that already have one
     */
    public function __invoke(array $grupoIds): array;
}
