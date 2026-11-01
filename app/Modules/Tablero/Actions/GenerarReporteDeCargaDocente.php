<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Data\CargaHoraria;
use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Data\SeccionDeReporte;
use App\Modules\Tablero\Enums\TipoDeColumna;
use App\Modules\Tablero\Support\AlcanceDelReporte;
use App\Modules\Tablero\Support\CifrasDelReporte;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

/**
 * Assigned hours follow the group's weekly schedule over the period; taught hours are the classes the teacher of the load gave.
 *
 * @phpstan-type Carga array{docenteId: int, docente: string, codigo: string, idioma: string, nivel: string, dias: string, asignados: int, dictados: int, confirmados: int}
 */
final readonly class GenerarReporteDeCargaDocente
{
    public function __construct(private AlcanceDelReporte $alcance, private FichasDePersonas $fichasDePersonas) {}

    public function __invoke(FiltrosDeReporte $filtros): ReporteGeneral
    {
        $periodo = $this->alcance->periodo($filtros);
        $grupos = $this->alcance->grupos($filtros);
        $cargas = $this->cargas($grupos);
        usort($cargas, fn (array $a, array $b): int => [$a['docente'], $a['codigo']] <=> [$b['docente'], $b['codigo']]);

        return new ReporteGeneral('Carga docente', $periodo, $this->alcance->descripcion($filtros, $grupos), [
            new SeccionDeReporte('Carga por docente', [
                new ColumnaDeReporte('Docente'), new ColumnaDeReporte('Grupos', TipoDeColumna::Entero), new ColumnaDeReporte('Horas asignadas', TipoDeColumna::Decimal),
                new ColumnaDeReporte('Horas dictadas', TipoDeColumna::Decimal), new ColumnaDeReporte('Avance', TipoDeColumna::Porcentaje),
            ], $this->porDocente($cargas)),
            new SeccionDeReporte('Carga por grupo', [
                new ColumnaDeReporte('Docente'), new ColumnaDeReporte('Grupo'), new ColumnaDeReporte('Idioma'), new ColumnaDeReporte('Nivel'), new ColumnaDeReporte('Días'),
                new ColumnaDeReporte('Horas asignadas', TipoDeColumna::Decimal), new ColumnaDeReporte('Horas dictadas', TipoDeColumna::Decimal), new ColumnaDeReporte('Horas confirmadas', TipoDeColumna::Decimal),
            ], array_map(fn (array $carga): array => [
                $carga['docente'], $carga['codigo'], $carga['idioma'], $carga['nivel'], $carga['dias'],
                CifrasDelReporte::horas($carga['asignados']), CifrasDelReporte::horas($carga['dictados']), CifrasDelReporte::horas($carga['confirmados']),
            ], $cargas)),
        ]);
    }

    /**
     * @param  list<Carga>  $cargas
     * @return list<list<string|int|float|null>>
     */
    private function porDocente(array $cargas): array
    {
        $filas = [];

        // Grouped by teacher, not by name: two teachers can share a name.
        foreach (collect($cargas)->groupBy('docenteId') as $delDocente) {
            $asignados = (int) $delDocente->sum('asignados');
            $dictados = (int) $delDocente->sum('dictados');
            $filas[] = [(string) ($delDocente->first()['docente'] ?? ''), $delDocente->count(), CifrasDelReporte::horas($asignados), CifrasDelReporte::horas($dictados), CifrasDelReporte::porcentaje($dictados, $asignados)];
        }

        return $filas;
    }

    /**
     * @param  list<HorarioDeGrupo>  $grupos
     * @return list<Carga>
     */
    private function cargas(array $grupos): array
    {
        $porId = [];

        foreach ($grupos as $grupo) {
            $porId[$grupo->grupoId] = $grupo;
        }

        $filas = DB::table('doc_cargas_academicas as c')->join('doc_docentes as d', 'd.id', '=', 'c.docente_id')
            ->whereIn('c.grupo_id', array_keys($porId))->get(['c.grupo_id', 'c.docente_id', 'd.persona_id']);
        $fichas = ($this->fichasDePersonas)(array_values($filas->pluck('persona_id')->map(fn (mixed $id): int => (int) $id)->unique()->all()));
        $minutos = $this->minutosDictados(array_keys($porId));
        $cargas = [];

        foreach ($filas as $fila) {
            $grupo = $porId[(int) $fila->grupo_id];
            $horaria = CargaHoraria::de($grupo);
            $dictado = $minutos->get($fila->grupo_id.'-'.$fila->docente_id);
            $cargas[] = [
                'docenteId' => (int) $fila->docente_id, 'docente' => $fichas[(int) $fila->persona_id]->nombreCompleto ?? '', 'codigo' => $grupo->codigo, 'idioma' => $grupo->idioma, 'nivel' => $horaria->nivel,
                'dias' => $horaria->dias, 'asignados' => $horaria->minutos, 'dictados' => (int) ($dictado->minutos ?? 0), 'confirmados' => (int) ($dictado->confirmados ?? 0),
            ];
        }

        return $cargas;
    }

    /**
     * @param  list<int>  $grupoIds
     * @return Collection<string, stdClass>
     */
    private function minutosDictados(array $grupoIds): Collection
    {
        return DB::table('doc_clases_dictadas')
            ->whereIn('grupo_id', $grupoIds)
            ->groupBy('grupo_id', 'docente_id')
            ->selectRaw('grupo_id, docente_id, (sum(extract(epoch from (hora_fin - hora_inicio))) / 60)::bigint as minutos')
            ->selectRaw('(coalesce(sum(extract(epoch from (hora_fin - hora_inicio))) filter (where confirmacion = ?), 0) / 60)::bigint as confirmados', [ConfirmacionDeClase::Confirmada->value])
            ->get()
            ->keyBy(fn (stdClass $fila): string => $fila->grupo_id.'-'.$fila->docente_id);
    }
}
