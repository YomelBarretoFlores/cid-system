<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Tablero\Data\FilaDeMatriculas;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Data\Matriculas;
use App\Modules\Tablero\Data\PuntoDeMatriculas;
use App\Modules\Tablero\Support\GruposDelPeriodo;
use App\Modules\Tablero\Support\PeriodoConsultado;
use Illuminate\Database\Query\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

// Periods are monthly cycles: "mes a mes" compares with the previous period and "año a año" with the same period number a year before.
final readonly class CalcularMatriculas
{
    private const int PERIODOS_DE_LA_SERIE = 12;

    public function __invoke(FiltrosDelTablero $filtros): Matriculas
    {
        $periodo = PeriodoConsultado::de($filtros->periodoId);
        $periodos = $this->anteriores($periodo)->reverse()->push((object) ['id' => $periodo->id, 'nombre' => $periodo->nombre])->values();
        $delAnioAnterior = DB::table('aca_periodos')->where(['anio' => $periodo->anio - 1, 'numero' => $periodo->numero])->first(['id', 'nombre']);
        $conteos = GruposDelPeriodo::deTodosLosPeriodos($filtros)
            ->join('mat_matriculas as m', 'm.grupo_id', '=', 'g.id')
            ->whereIn('g.periodo_id', [...$periodos->pluck('id')->all(), $delAnioAnterior->id ?? 0])
            ->groupBy('g.periodo_id')
            ->selectRaw('g.periodo_id, count(*) as total')
            ->pluck('total', 'periodo_id');
        $punto = fn (stdClass $fila): PuntoDeMatriculas => new PuntoDeMatriculas((int) $fila->id, (string) $fila->nombre, (int) ($conteos[$fila->id] ?? 0));
        $serie = array_values($periodos->map($punto)->all());

        return new Matriculas(
            total: $serie[count($serie) - 1]->total,
            anterior: $serie[count($serie) - 2] ?? null,
            delAnioAnterior: $delAnioAnterior === null ? null : $punto($delAnioAnterior),
            serie: $serie,
            porIdioma: $this->porIdioma($filtros),
        );
    }

    /**
     * @return Collection<int, stdClass>
     */
    private function anteriores(PeriodoConsultado $periodo): Collection
    {
        return DB::table('aca_periodos')
            ->where(fn (Builder $query): Builder => $query
                ->where('fecha_inicio', '<', $periodo->fechaInicio)
                ->orWhere(fn (Builder $mismoDia): Builder => $mismoDia->where('fecha_inicio', $periodo->fechaInicio)->where('id', '<', $periodo->id)))
            ->orderByDesc('fecha_inicio')
            ->orderByDesc('id')
            ->limit(self::PERIODOS_DE_LA_SERIE - 1)
            ->get(['id', 'nombre']);
    }

    /**
     * @return list<FilaDeMatriculas>
     */
    private function porIdioma(FiltrosDelTablero $filtros): array
    {
        return array_values(GruposDelPeriodo::consulta($filtros)
            ->join('mat_matriculas as m', 'm.grupo_id', '=', 'g.id')
            ->groupBy('i.nombre')
            ->selectRaw('i.nombre as idioma, count(*) as total')
            ->orderByDesc('total')
            ->orderBy('i.nombre')
            ->get()
            ->map(fn (stdClass $fila): FilaDeMatriculas => new FilaDeMatriculas((string) $fila->idioma, (int) $fila->total))
            ->all());
    }
}
