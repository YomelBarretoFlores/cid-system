<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Support;

use App\Modules\Certificacion\Data\AvanceDeNotasDelEstudiante;
use App\Modules\Certificacion\Data\FilaDeLaBoleta;
use App\Modules\Docencia\Data\AsistenciaDelEstudiante;
use App\Modules\Matricula\Data\HorarioSemanal;

final readonly class ReportesDelPeriodo
{
    /**
     * @param  array<int, AvanceDeNotasDelEstudiante>  $avances  by enrollment id
     * @param  array<int, AsistenciaDelEstudiante>  $asistencias  by enrollment id
     * @param  list<FilaDeLaBoleta>  $boleta
     */
    public function __construct(
        public int $periodoId,
        public string $periodo,
        public MatriculasDelEstudiante $datos,
        public array $avances,
        public array $asistencias,
        public array $boleta,
        public HorarioSemanal $horario,
    ) {}
}
