<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Data\SeccionDeReporte;
use App\Modules\Tablero\Enums\TipoDeColumna;
use App\Modules\Tablero\Support\AlcanceDelReporte;
use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use stdClass;

final readonly class GenerarReporteDeMatriculados
{
    public function __construct(private AlcanceDelReporte $alcance, private FichasDePersonas $fichasDePersonas) {}

    public function __invoke(FiltrosDeReporte $filtros): ReporteGeneral
    {
        $periodo = $this->alcance->periodo($filtros);
        $grupos = $this->alcance->grupos($filtros);
        $matriculas = $this->matriculas($grupos);

        return new ReporteGeneral('Matriculados', $periodo, $this->alcance->descripcion($filtros, $grupos), [
            new SeccionDeReporte('Resumen por grupo', [
                new ColumnaDeReporte('Grupo'), new ColumnaDeReporte('Idioma'), new ColumnaDeReporte('Nivel'), new ColumnaDeReporte('Ciclo', TipoDeColumna::Entero),
                new ColumnaDeReporte('Activos', TipoDeColumna::Entero), new ColumnaDeReporte('Retirados', TipoDeColumna::Entero),
            ], array_map(fn (HorarioDeGrupo $grupo): array => [
                $grupo->codigo, $grupo->idioma, $grupo->nivel->etiqueta(), $grupo->cicloNumero,
                $matriculas->where('grupo_id', $grupo->grupoId)->where('estado', EstadoMatricula::Activa->value)->count(),
                $matriculas->where('grupo_id', $grupo->grupoId)->where('estado', EstadoMatricula::Retirada->value)->count(),
            ], $grupos)),
            new SeccionDeReporte('Detalle de matriculados', [
                new ColumnaDeReporte('N.°', TipoDeColumna::Entero), new ColumnaDeReporte('Grupo'), new ColumnaDeReporte('Documento'), new ColumnaDeReporte('Apellidos y nombres'),
                new ColumnaDeReporte('Vínculo'), new ColumnaDeReporte('Condición'), new ColumnaDeReporte('Estado'), new ColumnaDeReporte('Matriculado el'),
            ], $this->detalle($grupos, $matriculas)),
        ]);
    }

    /**
     * @param  list<HorarioDeGrupo>  $grupos
     * @return Collection<int, stdClass>
     */
    private function matriculas(array $grupos): Collection
    {
        $matriculas = DB::table('mat_matriculas as m')
            ->join('per_personas as p', 'p.id', '=', 'm.persona_id')
            ->join('per_tipos_vinculo as v', 'v.codigo', '=', 'p.tipo_vinculo')
            ->whereIn('m.grupo_id', array_map(fn (HorarioDeGrupo $grupo): int => $grupo->grupoId, $grupos))
            ->get(['m.grupo_id', 'm.persona_id', 'm.condicion', 'm.estado', 'm.matriculado_en', 'v.nombre as vinculo']);
        $fichas = ($this->fichasDePersonas)(array_values($matriculas->pluck('persona_id')->map(fn (mixed $id): int => (int) $id)->unique()->all()));

        return $matriculas->each(function (stdClass $matricula) use ($fichas): void {
            $matricula->nombre = $fichas[(int) $matricula->persona_id]->nombreCompleto ?? '';
            $matricula->documento = $fichas[(int) $matricula->persona_id]->documento ?? '';
        });
    }

    /**
     * @param  list<HorarioDeGrupo>  $grupos
     * @param  Collection<int, stdClass>  $matriculas
     * @return list<list<string|int|null>>
     */
    private function detalle(array $grupos, Collection $matriculas): array
    {
        $filas = [];

        foreach ($grupos as $grupo) {
            foreach ($matriculas->where('grupo_id', $grupo->grupoId)->sortBy('nombre') as $matricula) {
                $filas[] = [
                    count($filas) + 1, $grupo->codigo, $matricula->documento, $matricula->nombre,
                    (string) $matricula->vinculo,
                    CondicionMatricula::from((string) $matricula->condicion)->etiqueta(),
                    EstadoMatricula::from((string) $matricula->estado)->etiqueta(),
                    CarbonImmutable::parse((string) $matricula->matriculado_en)->timezone('America/Lima')->format('d/m/Y'),
                ];
            }
        }

        return $filas;
    }
}
