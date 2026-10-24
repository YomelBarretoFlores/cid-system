<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

final readonly class ResumenDeCarga
{
    /**
     * @param  list<CargaHoraria>  $cargas
     * @param  array<string, string>  $totalesPorPeriodo  period name => total hours
     */
    public function __construct(
        public array $cargas,
        public array $totalesPorPeriodo,
    ) {}
}
