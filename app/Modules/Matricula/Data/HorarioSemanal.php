<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Data;

final readonly class HorarioSemanal
{
    /**
     * @param  array<int, string>  $dias  day name by ISO day of week, only days with classes
     * @param  list<FilaDelHorario>  $filas  by start time
     * @param  list<GrupoDelHorario>  $grupos
     */
    public function __construct(
        public array $dias,
        public array $filas,
        public array $grupos,
    ) {}
}
