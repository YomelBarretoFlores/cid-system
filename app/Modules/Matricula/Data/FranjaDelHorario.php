<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class FranjaDelHorario
{
    /**
     * @param  array<int, list<CeldaDelHorario>>  $celdas  by ISO day of week
     */
    public function __construct(
        public string $horas,
        public array $celdas = [],
        public bool $sinClases = false,
    ) {}
}
