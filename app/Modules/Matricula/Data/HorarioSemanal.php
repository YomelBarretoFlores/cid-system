<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class HorarioSemanal
{
    /**
     * @param  array<int, string>  $dias  day name by ISO day of week: Monday to Saturday, Sunday only when used
     * @param  list<FranjaDelHorario>  $franjas  30-minute rows from the first class to the last one
     * @param  list<GrupoDelHorario>  $grupos  numbered and colored in this order
     */
    public function __construct(
        public array $dias,
        public array $franjas,
        public array $grupos,
    ) {}
}
