<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Data;

use App\Modules\Docencia\Enums\EstadoAsistencia;
use Carbon\CarbonImmutable;

final readonly class AsistenciaDeClase
{
    public function __construct(
        public CarbonImmutable $fecha,
        public string $horario,
        public EstadoAsistencia $inicio,
        public ?EstadoAsistencia $cierre,
        public EstadoAsistencia $efectivo,
        public string $estadoDeLaClase,
        public ?string $tema,
    ) {}
}
