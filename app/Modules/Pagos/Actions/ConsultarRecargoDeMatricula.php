<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Academico\Contracts\MatriculaExtemporanea;
use App\Modules\Pagos\Contracts\RecargoDeMatricula;
use App\Modules\Pagos\Models\Configuracion;
use Carbon\CarbonInterface;

final readonly class ConsultarRecargoDeMatricula implements RecargoDeMatricula
{
    public function __construct(private MatriculaExtemporanea $matriculaExtemporanea) {}

    public function __invoke(CarbonInterface $fecha): ?int
    {
        // A missing row (a test truncation) falls back to the default the migration inserts.
        $porcentaje = Configuracion::query()->find(Configuracion::FILA)->recargo_matricula_extemporanea ?? Configuracion::RECARGO_POR_DEFECTO;

        return $porcentaje > 0 && ($this->matriculaExtemporanea)($fecha) ? $porcentaje : null;
    }
}
