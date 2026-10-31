<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Certificacion\Contracts\AvanceDeNotas;
use App\Modules\Certificacion\Contracts\BoletaDeNotas;
use App\Modules\Docencia\Contracts\AsistenciaDeMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Support\MatriculasDelEstudiante;
use App\Modules\Matricula\Support\ReportesDelPeriodo;

final readonly class ResumirReportesDelPeriodo
{
    public function __construct(
        private AvanceDeNotas $avanceDeNotas,
        private AsistenciaDeMatricula $asistenciaDeMatricula,
        private BoletaDeNotas $boletaDeNotas,
        private ArmarHorarioSemanal $armarHorario,
    ) {}

    public function __invoke(MatriculasDelEstudiante $datos): ReportesDelPeriodo
    {
        $primero = $datos->grupos[$datos->matriculas->firstOrFail()->grupo_id];

        return new ReportesDelPeriodo(
            periodoId: $primero->periodoId,
            periodo: $primero->periodo,
            datos: $datos,
            avances: $datos->matriculas->mapWithKeys(fn (Matricula $matricula): array => [$matricula->id => ($this->avanceDeNotas)($matricula->grupo_id, $matricula->id)])->all(),
            asistencias: $datos->matriculas->mapWithKeys(fn (Matricula $matricula): array => [$matricula->id => ($this->asistenciaDeMatricula)($matricula->id, $matricula->grupo_id)])->all(),
            boleta: ($this->boletaDeNotas)($datos->grupoPorMatricula()),
            horario: ($this->armarHorario)($datos),
        );
    }
}
