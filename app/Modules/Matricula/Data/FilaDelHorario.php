<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class FilaDelHorario
{
    /**
     * @param  array<int, list<string>>  $celdas  group codes by ISO day of week
     */
    public function __construct(
        public string $horas,
        public array $celdas,
    ) {}
}
