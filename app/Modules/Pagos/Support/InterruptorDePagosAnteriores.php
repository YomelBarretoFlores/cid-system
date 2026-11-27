<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Support;

use App\Modules\Pagos\Contracts\PagosAnterioresPermitidos;
use App\Modules\Pagos\Models\Configuracion;

final readonly class InterruptorDePagosAnteriores implements PagosAnterioresPermitidos
{
    public function __invoke(): bool
    {
        return (Configuracion::query()->find(Configuracion::FILA) ?? new Configuracion)->permite_pagos_anteriores_al_sistema;
    }
}
