<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Docencia\Contracts\DocentesDeGrupos;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Data\SeccionDeReporte;
use App\Modules\Tablero\Enums\TipoDeColumna;
use App\Modules\Tablero\Support\AlcanceDelReporte;
use App\Modules\Tablero\Support\ResultadosPorGrupo;
use stdClass;

// Counts come from the acta's final grades; a group without an acta only shows its active students.
final readonly class GenerarReporteDeNotas
{
    public function __construct(
        private AlcanceDelReporte $alcance,
        private DocentesDeGrupos $docentesDeGrupos,
        private EstudiantesDelGrupo $estudiantesDelGrupo,
    ) {}

    public function __invoke(FiltrosDeReporte $filtros): ReporteGeneral
    {
        $periodo = $this->alcance->periodo($filtros);
        $grupos = $this->alcance->grupos($filtros);
        $ids = array_map(fn (HorarioDeGrupo $grupo): int => $grupo->grupoId, $grupos);
        $docentes = ($this->docentesDeGrupos)($ids);
        $actas = ResultadosPorGrupo::actas($ids);

        return new ReporteGeneral('Notas por periodo', $periodo, $this->alcance->descripcion($filtros, $grupos), [
            new SeccionDeReporte('Notas por grupo', [
                new ColumnaDeReporte('Grupo'), new ColumnaDeReporte('Idioma'), new ColumnaDeReporte('Nivel'), new ColumnaDeReporte('Ciclo', TipoDeColumna::Entero),
                new ColumnaDeReporte('Docente'), new ColumnaDeReporte('Estado del acta'), new ColumnaDeReporte('Estudiantes', TipoDeColumna::Entero),
                new ColumnaDeReporte('Aprobados', TipoDeColumna::Entero), new ColumnaDeReporte('Desaprobados', TipoDeColumna::Entero), new ColumnaDeReporte('NSP', TipoDeColumna::Entero),
                new ColumnaDeReporte('Promedio del grupo', TipoDeColumna::Decimal),
            ], array_map(fn (HorarioDeGrupo $grupo): array => [
                $grupo->codigo, $grupo->idioma, $grupo->nivel->etiqueta(), $grupo->cicloNumero, $docentes[$grupo->grupoId] ?? 'Sin docente',
                ...$this->resultado($grupo, $actas[$grupo->grupoId] ?? null),
            ], $grupos)),
        ]);
    }

    /**
     * @return list<string|int|float|null>
     */
    private function resultado(HorarioDeGrupo $grupo, ?stdClass $acta): array
    {
        if (! $acta instanceof stdClass) {
            return ['Sin acta', count(($this->estudiantesDelGrupo)($grupo->grupoId)), null, null, null, null];
        }

        return [
            EstadoActa::from((string) $acta->estado)->etiqueta(), (int) $acta->estudiantes, (int) $acta->aprobados, (int) $acta->desaprobados, (int) $acta->nsp,
            is_numeric($acta->promedio) ? round((float) $acta->promedio, 2) : null,
        ];
    }
}
