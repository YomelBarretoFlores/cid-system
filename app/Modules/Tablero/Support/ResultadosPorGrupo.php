<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ResultadoDeGrupo;
use Illuminate\Support\Facades\DB;
use stdClass;

// The grade counts of each group in the report's scope, read from the acta's final grades (Tablero reads other modules' tables directly).
final readonly class ResultadosPorGrupo
{
    public function __construct(
        private AlcanceDelReporte $alcance,
        private DocentesDeGrupos $docentesDeGrupos,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
    ) {}

    /**
     * @return list<ResultadoDeGrupo>
     */
    public function de(FiltrosDeReporte $filtros): array
    {
        $grupos = $this->alcance->grupos($filtros);
        $ids = array_map(fn (HorarioDeGrupo $grupo): int => $grupo->grupoId, $grupos);
        $docentes = ($this->docentesDeGrupos)($ids);
        $programas = DB::table('aca_grupos as g')->join('aca_programas as p', 'p.id', '=', 'g.programa_id')->whereIn('g.id', $ids)->pluck('p.nombre', 'g.id')->all();
        $actas = self::actas($ids);

        return array_map(fn (HorarioDeGrupo $grupo): ResultadoDeGrupo => $this->resultado(
            $grupo,
            (string) ($programas[$grupo->grupoId] ?? '—'),
            $docentes[$grupo->grupoId] ?? 'Sin docente',
            $actas[$grupo->grupoId] ?? null,
        ), $grupos);
    }

    private function resultado(HorarioDeGrupo $grupo, string $programa, string $docente, ?stdClass $acta): ResultadoDeGrupo
    {
        if (! $acta instanceof stdClass || (int) $acta->estudiantes === 0) {
            return new ResultadoDeGrupo($grupo, $programa, $docente, count(($this->estudiantesDelGrupo)($grupo->grupoId)), 0, 0, 0);
        }

        return new ResultadoDeGrupo($grupo, $programa, $docente, 0, (int) $acta->aprobados, (int) $acta->desaprobados, (int) $acta->nsp);
    }

    /**
     * @param  list<int>  $grupoIds
     * @return array<int, stdClass> group id => estado, estudiantes, promedio, aprobados, desaprobados, nsp
     */
    public static function actas(array $grupoIds): array
    {
        return DB::table('cer_actas as a')
            ->leftJoin('cer_notas_finales as n', 'n.acta_id', '=', 'a.id')
            ->whereIn('a.grupo_id', $grupoIds)
            ->groupBy('a.grupo_id', 'a.estado')
            ->selectRaw('a.grupo_id, a.estado, count(n.id) as estudiantes, avg(n.promedio) as promedio')
            ->selectRaw('count(n.id) filter (where n.estado_final = ?) as aprobados', [EstadoFinal::Aprobado->value])
            ->selectRaw('count(n.id) filter (where n.estado_final = ?) as desaprobados', [EstadoFinal::Desaprobado->value])
            ->selectRaw('count(n.id) filter (where n.estado_final = ?) as nsp', [EstadoFinal::Nsp->value])
            ->get()
            ->keyBy(fn (stdClass $acta): int => (int) $acta->grupo_id)
            ->all();
    }
}
