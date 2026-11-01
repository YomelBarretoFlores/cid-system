<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Docencia\Enums\ConfirmacionDeClase;
use App\Modules\Docencia\Enums\EstadoAsistencia;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Data\SeccionDeReporte;
use App\Modules\Tablero\Enums\TipoDeColumna;
use App\Modules\Tablero\Support\AlcanceDelReporte;
use App\Modules\Tablero\Support\CifrasDelReporte;
use App\Modules\Tablero\Support\ClasesProgramadas;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GenerarReporteDeAsistencia
{
    public function __construct(
        private AlcanceDelReporte $alcance,
        private DocentesDeGrupos $docentesDeGrupos,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
        private FichasDePersonas $fichasDePersonas,
    ) {}

    public function __invoke(FiltrosDeReporte $filtros): ReporteGeneral
    {
        $periodo = $this->alcance->periodo($filtros);
        $grupos = $this->alcance->grupos($filtros);
        $ids = array_map(fn (HorarioDeGrupo $grupo): int => $grupo->grupoId, $grupos);

        return new ReporteGeneral('Asistencia por grupo', $periodo, $this->alcance->descripcion($filtros, $grupos), [
            new SeccionDeReporte('Clases por grupo', [
                new ColumnaDeReporte('Grupo'), new ColumnaDeReporte('Docente'), new ColumnaDeReporte('Programadas', TipoDeColumna::Entero), new ColumnaDeReporte('Dictadas', TipoDeColumna::Entero),
                new ColumnaDeReporte('Confirmadas', TipoDeColumna::Entero), new ColumnaDeReporte('Observadas', TipoDeColumna::Entero), new ColumnaDeReporte('Sin confirmar', TipoDeColumna::Entero),
            ], $this->clases($grupos, ($this->docentesDeGrupos)($ids))),
            new SeccionDeReporte('Asistencia por estudiante', [
                new ColumnaDeReporte('Grupo'), new ColumnaDeReporte('Documento'), new ColumnaDeReporte('Apellidos y nombres'), new ColumnaDeReporte('Clases dictadas', TipoDeColumna::Entero),
                new ColumnaDeReporte('Presente', TipoDeColumna::Entero), new ColumnaDeReporte('Ausente', TipoDeColumna::Entero),
                new ColumnaDeReporte('% sobre dictadas', TipoDeColumna::Porcentaje), new ColumnaDeReporte('% sobre programadas', TipoDeColumna::Porcentaje),
            ], $this->estudiantes($grupos)),
        ]);
    }

    /**
     * @param  list<HorarioDeGrupo>  $grupos
     * @param  array<int, string>  $docentes
     * @return list<list<string|int>>
     */
    private function clases(array $grupos, array $docentes): array
    {
        $clases = DB::table('doc_clases_dictadas')->whereIn('grupo_id', array_map(fn (HorarioDeGrupo $grupo): int => $grupo->grupoId, $grupos))->get(['grupo_id', 'confirmacion']);

        return array_map(function (HorarioDeGrupo $grupo) use ($clases, $docentes): array {
            $delGrupo = $clases->where('grupo_id', $grupo->grupoId);
            $confirmadas = $delGrupo->where('confirmacion', ConfirmacionDeClase::Confirmada->value)->count();
            $observadas = $delGrupo->where('confirmacion', ConfirmacionDeClase::Observada->value)->count();

            return [$grupo->codigo, $docentes[$grupo->grupoId] ?? 'Sin docente', ClasesProgramadas::de($grupo), $delGrupo->count(), $confirmadas, $observadas, $delGrupo->count() - $confirmadas - $observadas];
        }, $grupos);
    }

    /**
     * @param  list<HorarioDeGrupo>  $grupos
     * @return list<list<string|int|null>>
     */
    private function estudiantes(array $grupos): array
    {
        $filas = [];

        foreach ($grupos as $grupo) {
            $estudiantes = ($this->estudiantesDelGrupo)($grupo->grupoId);
            $marcas = $this->marcas(array_map(fn (EstudianteDelGrupo $estudiante): int => $estudiante->matriculaId, $estudiantes));
            $fichas = ($this->fichasDePersonas)(array_map(fn (EstudianteDelGrupo $estudiante): int => $estudiante->personaId, $estudiantes));
            $programadas = ClasesProgramadas::de($grupo);

            foreach ($estudiantes as $estudiante) {
                $marca = $marcas->get($estudiante->matriculaId);
                [$dictadas, $presentes] = [(int) ($marca->dictadas ?? 0), (int) ($marca->presentes ?? 0)];
                $filas[] = [
                    $grupo->codigo, $fichas[$estudiante->personaId]->documento ?? '', $estudiante->nombreCompleto, $dictadas, $presentes, $dictadas - $presentes,
                    CifrasDelReporte::porcentaje($presentes, $dictadas), CifrasDelReporte::porcentaje($presentes, $programadas),
                ];
            }
        }

        return $filas;
    }

    /**
     * A student is present when marked present at the first call and not absent at the closing call.
     *
     * @param  list<int>  $matriculaIds
     * @return Collection<int, stdClass>
     */
    private function marcas(array $matriculaIds): Collection
    {
        return DB::table('doc_asistencias')
            ->whereIn('matricula_id', $matriculaIds)
            ->groupBy('matricula_id')
            ->selectRaw('matricula_id, count(*) as dictadas, count(*) filter (where estado = ? and estado_cierre is distinct from ?) as presentes', [EstadoAsistencia::Presente->value, EstadoAsistencia::Falta->value])
            ->get()
            ->keyBy(fn (stdClass $fila): int => (int) $fila->matricula_id);
    }
}
