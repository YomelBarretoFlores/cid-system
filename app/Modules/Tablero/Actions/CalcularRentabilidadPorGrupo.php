<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tablero\Data\FilaDeRentabilidad;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\RentabilidadPorGrupo;
use App\Modules\Tablero\Support\GruposDelPeriodo;
use App\Modules\Tablero\Support\PeriodoConsultado;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class CalcularRentabilidadPorGrupo
{
    public function __invoke(FiltrosDelTablero $filtros): RentabilidadPorGrupo
    {
        PeriodoConsultado::de($filtros->periodoId);

        $grupos = GruposDelPeriodo::consulta($filtros)
            ->orderBy('i.nombre')
            ->orderBy('c.numero')
            ->orderBy('g.codigo')
            ->get(['g.id', 'g.codigo', 'i.nombre as idioma', 'c.nivel']);
        $ids = array_values(array_map(intval(...), $grupos->pluck('id')->all()));
        $matriculas = DB::table('mat_matriculas')->whereIn('grupo_id', $ids)->groupBy('grupo_id')->selectRaw('grupo_id, count(*) as total')->pluck('total', 'grupo_id');
        $ingresos = $this->ingresos($ids);
        $horas = $this->horas($ids);

        $filas = array_values($grupos->map(function (stdClass $grupo) use ($matriculas, $ingresos, $horas): FilaDeRentabilidad {
            $carga = $this->carga($horas->get($grupo->id, new Collection));

            return new FilaDeRentabilidad(
                grupoId: (int) $grupo->id,
                codigo: (string) $grupo->codigo,
                idioma: (string) $grupo->idioma,
                nivel: NivelCurricular::from((string) $grupo->nivel)->etiqueta(),
                matriculas: (int) ($matriculas[$grupo->id] ?? 0),
                ingresosCentimos: (int) ($ingresos[$grupo->id] ?? 0),
                horasCentesimas: $carga['horasCentesimas'],
                costoCentimos: $carga['costoCentimos'],
                horasSinCostoCentesimas: $carga['horasSinCostoCentesimas'],
            );
        })->all());

        return new RentabilidadPorGrupo(
            filas: $filas,
            ingresosCentimos: array_sum(array_map(fn (FilaDeRentabilidad $fila): int => $fila->ingresosCentimos, $filas)),
            costoCentimos: array_sum(array_map(fn (FilaDeRentabilidad $fila): int => $fila->costoCentimos, $filas)),
            horasSinCostoCentesimas: array_sum(array_map(fn (FilaDeRentabilidad $fila): int => $fila->horasSinCostoCentesimas, $filas)),
        );
    }

    /**
     * Validated enrollment fees of the groups' enrollments, withdrawn ones included: that money was collected.
     *
     * @param  list<int>  $ids
     * @return Collection<array-key, mixed>
     */
    private function ingresos(array $ids): Collection
    {
        return DB::table('mat_matriculas as m')
            ->join('pag_pagos as p', 'p.id', '=', 'm.pago_id')
            ->join('pag_conceptos as k', 'k.id', '=', 'p.concepto_id')
            ->whereIn('m.grupo_id', $ids)
            ->where('p.estado', EstadoPago::Validado->value)
            ->where('k.tipo', TipoConcepto::Matricula->value)
            ->groupBy('m.grupo_id')
            ->selectRaw('m.grupo_id, sum((p.monto * 100)::bigint) as centimos')
            ->pluck('centimos', 'grupo_id');
    }

    /**
     * Minutes taught per group and hourly cost in force on each class date for the teacher's degree, or else employment condition.
     *
     * @param  list<int>  $ids
     * @return Collection<array-key, Collection<int, stdClass>>
     */
    private function horas(array $ids): Collection
    {
        return DB::table('doc_clases_dictadas as cl')
            ->join('doc_docentes as d', 'd.id', '=', 'cl.docente_id')
            // Same rule as the hours report: the degree rate in force wins; before it, the employment condition rate applies.
            ->leftJoin('doc_costos_hora as chg', fn (JoinClause $join): JoinClause => $join
                ->on('chg.grado_academico_id', '=', 'd.grado_academico_id')
                ->on('chg.vigente_desde', '<=', 'cl.fecha')
                ->on(DB::raw("coalesce(chg.vigente_hasta, 'infinity'::date)"), '>=', 'cl.fecha'))
            ->leftJoin('doc_costos_hora as chc', fn (JoinClause $join): JoinClause => $join
                ->on('chc.condicion_laboral_id', '=', 'd.condicion_laboral_id')
                ->on('chc.vigente_desde', '<=', 'cl.fecha')
                ->on(DB::raw("coalesce(chc.vigente_hasta, 'infinity'::date)"), '>=', 'cl.fecha'))
            ->whereIn('cl.grupo_id', $ids)
            ->groupByRaw('cl.grupo_id, coalesce(chg.costo_hora, chc.costo_hora)')
            ->selectRaw('cl.grupo_id, (coalesce(chg.costo_hora, chc.costo_hora) * 100)::bigint as costo, (sum(extract(epoch from (cl.hora_fin - cl.hora_inicio))) / 60)::bigint as minutos')
            ->get()
            ->groupBy('grupo_id');
    }

    /**
     * Hours are rounded once per rate and the cost is computed from those hours, as in the teachers' hour reports.
     *
     * @param  Collection<int, stdClass>  $lineas
     * @return array{horasCentesimas: int, costoCentimos: int, horasSinCostoCentesimas: int}
     */
    private function carga(Collection $lineas): array
    {
        $carga = ['horasCentesimas' => 0, 'costoCentimos' => 0, 'horasSinCostoCentesimas' => 0];

        foreach ($lineas as $linea) {
            $centesimas = intdiv((int) $linea->minutos * 100 + 30, 60);

            if ($linea->costo === null) {
                $carga['horasSinCostoCentesimas'] += $centesimas;

                continue;
            }

            $carga['horasCentesimas'] += $centesimas;
            $carga['costoCentimos'] += intdiv($centesimas * (int) $linea->costo + 50, 100);
        }

        return $carga;
    }
}
