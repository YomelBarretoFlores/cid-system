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

// The previous system's monthly report: passed and failed students of each language, per program and overall; NSP counts as failed.
final readonly class GenerarReporteMensual
{
    public function __construct(
        private AlcanceDelReporte $alcance,
        private ResultadosPorGrupo $resultados,
    ) {}

    public function __invoke(FiltrosDeReporte $filtros): ReporteGeneral
    {
        $resultados = $this->resultados->de($filtros);
        $programas = collect($resultados)->map(fn (ResultadoDeGrupo $fila): string => $fila->programa)->unique()->sort()->values()->all();
        $programas = array_values($programas);
        $porIdioma = collect($resultados)->sortBy(fn (ResultadoDeGrupo $fila): string => $fila->grupo->idioma)->groupBy(fn (ResultadoDeGrupo $fila): string => $fila->grupo->idioma);

        $columnas = [new ColumnaDeReporte('Idioma')];

        foreach ([...$programas, null] as $programa) {
            foreach (['Aprobados', 'Desaprobados', 'Total'] as $cifra) {
                $columnas[] = new ColumnaDeReporte($programa === null ? $cifra : "{$programa} · {$cifra}", TipoDeColumna::Entero, $programa ?? 'Total', $cifra);
            }
        }

        return new ReporteGeneral(
            'Reporte mensual de alumnos por idioma',
            $this->alcance->periodo($filtros),
            $this->alcance->descripcion($filtros, array_map(fn (ResultadoDeGrupo $fila): HorarioDeGrupo => $fila->grupo, $resultados)),
            [new SeccionDeReporte(
                'Alumnos por idioma',
                $columnas,
                array_values($porIdioma->map(fn ($filas, string $idioma): array => $this->fila($idioma, $programas, array_values($filas->all())))->all()),
                [$this->fila('Total', $programas, $resultados)],
            )],
            formal: true,
        );
    }

    /**
     * @param  list<string>  $programas
     * @param  list<ResultadoDeGrupo>  $filas
     * @return list<string|int>
     */
    private function fila(string $etiqueta, array $programas, array $filas): array
    {
        $fila = [$etiqueta];

        foreach ([...$programas, null] as $programa) {
            $delPrograma = array_filter($filas, fn (ResultadoDeGrupo $resultado): bool => $programa === null || $resultado->programa === $programa);
            $aprobados = array_sum(array_map(fn (ResultadoDeGrupo $resultado): int => $resultado->aprobados, $delPrograma));
            $desaprobados = array_sum(array_map(fn (ResultadoDeGrupo $resultado): int => $resultado->desaprobados + $resultado->nsp, $delPrograma));
            $fila[] = $aprobados;
            $fila[] = $desaprobados;
            $fila[] = $aprobados + $desaprobados;
        }

        return $fila;
    }
}
