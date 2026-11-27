<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Data\ResultadoDeGrupo;
use App\Modules\Tablero\Data\SeccionDeReporte;
use App\Modules\Tablero\Enums\TipoDeColumna;
use App\Modules\Tablero\Support\AlcanceDelReporte;
use App\Modules\Tablero\Support\ResultadosPorGrupo;

// The previous system's «Reporte estadístico por idioma»: one table per language and program, its groups by level and cycle, with subtotals.
final readonly class GenerarReporteEstadistico
{
    public function __construct(
        private AlcanceDelReporte $alcance,
        private ResultadosPorGrupo $resultados,
    ) {}

    public function __invoke(FiltrosDeReporte $filtros): ReporteGeneral
    {
        $resultados = $this->resultados->de($filtros);
        $porSeccion = collect($resultados)
            ->sortBy(fn (ResultadoDeGrupo $fila): array => [$fila->grupo->idioma, $fila->programa, $fila->grupo->nivel->orden(), $fila->grupo->cicloNumero, $fila->docente])
            ->groupBy(fn (ResultadoDeGrupo $fila): string => "{$fila->grupo->idioma} · {$fila->programa}");

        return new ReporteGeneral(
            'Reporte estadístico por idioma',
            $this->alcance->periodo($filtros),
            $this->alcance->descripcion($filtros, array_map(fn (ResultadoDeGrupo $fila): HorarioDeGrupo => $fila->grupo, $resultados)),
            array_values($porSeccion->map(fn ($filas, string $titulo): SeccionDeReporte => $this->seccion($titulo, array_values($filas->all())))->all()),
            formal: true,
        );
    }

    /**
     * @param  list<ResultadoDeGrupo>  $filas
     */
    private function seccion(string $titulo, array $filas): SeccionDeReporte
    {
        $porNivel = collect($filas)->groupBy(fn (ResultadoDeGrupo $fila): string => $fila->grupo->nivel->etiqueta());
        $subtotales = $porNivel->count() > 1
            ? array_values($porNivel->map(fn ($delNivel, string $nivel): array => $this->total("Total {$nivel}", array_values($delNivel->all())))->all())
            : [];

        return new SeccionDeReporte($titulo, [
            new ColumnaDeReporte('Nivel'), new ColumnaDeReporte('Ciclo', TipoDeColumna::Entero), new ColumnaDeReporte('Docente'), new ColumnaDeReporte('Grupo'),
            new ColumnaDeReporte('Inscritos', TipoDeColumna::Entero), new ColumnaDeReporte('Aprobados', TipoDeColumna::Entero),
            new ColumnaDeReporte('Desaprobados', TipoDeColumna::Entero), new ColumnaDeReporte('NSP', TipoDeColumna::Entero), new ColumnaDeReporte('Total', TipoDeColumna::Entero),
        ], array_map(fn (ResultadoDeGrupo $fila): array => [
            $fila->grupo->nivel->etiqueta(), $fila->grupo->cicloNumero, $fila->docente, $fila->grupo->codigo,
            $fila->inscritos, $fila->aprobados, $fila->desaprobados, $fila->nsp, $fila->total(),
        ], $filas), [...$subtotales, $this->total('Total', $filas)], 'Idioma - Modalidad: '.str_replace(' · ', ' - ', $titulo));
    }

    /**
     * @param  list<ResultadoDeGrupo>  $filas
     * @return list<string|int|null>
     */
    private function total(string $etiqueta, array $filas): array
    {
        $suma = fn (callable $valor): int => array_sum(array_map($valor, $filas));

        return [
            $etiqueta, null, null, null,
            $suma(fn (ResultadoDeGrupo $fila): int => $fila->inscritos), $suma(fn (ResultadoDeGrupo $fila): int => $fila->aprobados),
            $suma(fn (ResultadoDeGrupo $fila): int => $fila->desaprobados), $suma(fn (ResultadoDeGrupo $fila): int => $fila->nsp),
            $suma(fn (ResultadoDeGrupo $fila): int => $fila->total()),
        ];
    }
}
