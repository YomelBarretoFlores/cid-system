<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Academico\Contracts\HorariosDeGrupos;
use App\Modules\Docencia\Contracts\AsistenciaDeMatricula;
use App\Modules\Docencia\Data\AsistenciaDeClase;
use App\Modules\Docencia\Data\AsistenciaDelEstudiante;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Support\ClasesDelHorario;
use App\Modules\Docencia\Support\EstadoDeLaClase;

final readonly class ResumirAsistenciaDeMatricula implements AsistenciaDeMatricula
{
    public function __construct(private HorariosDeGrupos $horariosDeGrupos) {}

    public function __invoke(int $matriculaId, int $grupoId): AsistenciaDelEstudiante
    {
        $horario = ($this->horariosDeGrupos)([$grupoId])[$grupoId] ?? null;
        $ahora = ClasesDelHorario::ahora();

        $clases = ClaseDictada::query()
            ->join('doc_asistencias', 'doc_asistencias.clase_id', '=', 'doc_clases_dictadas.id')
            ->where('doc_asistencias.matricula_id', $matriculaId)
            ->orderBy('doc_clases_dictadas.fecha')
            ->orderBy('doc_clases_dictadas.hora_inicio')
            ->get(['doc_clases_dictadas.*', 'doc_asistencias.estado as estado_inicio', 'doc_asistencias.estado_cierre as estado_al_cierre'])
            ->map(function (ClaseDictada $clase) use ($ahora): AsistenciaDeClase {
                $inicio = EstadoAsistencia::from((string) $clase->getAttribute('estado_inicio'));
                $cierre = EstadoAsistencia::tryFrom((string) $clase->getAttribute('estado_al_cierre'));

                return new AsistenciaDeClase(
                    fecha: $clase->fecha,
                    horario: substr($clase->hora_inicio, 0, 5).' – '.substr($clase->hora_fin, 0, 5),
                    inicio: $inicio,
                    cierre: $cierre,
                    efectivo: EstadoAsistencia::efectivo($inicio, $cierre),
                    estadoDeLaClase: EstadoDeLaClase::de($clase, $ahora)->etiqueta(),
                    tema: $clase->tema,
                );
            });

        return new AsistenciaDelEstudiante(array_values($clases->all()), $horario === null ? 0 : count(ClasesDelHorario::delPeriodo($horario)));
    }
}
