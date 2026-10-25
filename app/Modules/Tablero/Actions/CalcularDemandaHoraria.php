<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\FranjaHoraria;
use App\Modules\Tablero\Support\GruposDelPeriodo;
use App\Modules\Tablero\Support\PeriodoConsultado;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class CalcularDemandaHoraria
{
    /**
     * Schedule slots (start and end time) most requested first, each group counted once per slot.
     *
     * @return list<FranjaHoraria>
     */
    public function __invoke(FiltrosDelTablero $filtros): array
    {
        PeriodoConsultado::de($filtros->periodoId);

        $horarios = GruposDelPeriodo::consulta($filtros)
            ->join('aca_horarios as h', 'h.grupo_id', '=', 'g.id')
            ->get(['g.id', 'g.cupo_maximo', 'h.dia_semana', 'h.hora_inicio', 'h.hora_fin']);
        $activas = DB::table('mat_matriculas')
            ->whereIn('grupo_id', $horarios->pluck('id')->unique()->all())
            ->where('estado', EstadoMatricula::Activa->value)
            ->groupBy('grupo_id')
            ->selectRaw('grupo_id, count(*) as total')
            ->pluck('total', 'grupo_id');

        return array_values($horarios
            ->groupBy(fn (stdClass $horario): string => "{$horario->hora_inicio}-{$horario->hora_fin}")
            ->map(fn (Collection $franja): FranjaHoraria => $this->franja($franja, $activas))
            ->sortBy(fn (FranjaHoraria $franja): array => [-$franja->matriculados, $franja->horaInicio, $franja->horaFin])
            ->all());
    }

    /**
     * @param  Collection<int, stdClass>  $franja
     * @param  Collection<array-key, mixed>  $activas
     */
    private function franja(Collection $franja, Collection $activas): FranjaHoraria
    {
        $grupos = $franja->unique('id');
        $dias = array_values(array_map(intval(...), $franja->pluck('dia_semana')->unique()->sort()->all()));

        return new FranjaHoraria(
            horaInicio: substr((string) $franja->first()?->hora_inicio, 0, 5),
            horaFin: substr((string) $franja->first()?->hora_fin, 0, 5),
            dias: $dias,
            grupos: $grupos->count(),
            matriculados: (int) $grupos->sum(fn (stdClass $grupo): int => (int) ($activas[$grupo->id] ?? 0)),
            cupo: (int) $grupos->sum('cupo_maximo'),
        );
    }
}
