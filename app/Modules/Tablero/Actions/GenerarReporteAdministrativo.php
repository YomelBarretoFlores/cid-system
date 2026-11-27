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
use App\Modules\Tablero\Support\PagosDeMatriculas;
use App\Modules\Tablero\Support\ResultadosPorGrupo;
use Illuminate\Support\Collection;
use stdClass;

// The previous system's administrative report: the enrollments of each group by how they paid. The system has no payment modalities
// (normal, scholarship), so the fee's link type stands in for them; the place of payment is the payment method.
final readonly class GenerarReporteAdministrativo
{
    public function __construct(
        private AlcanceDelReporte $alcance,
        private ResultadosPorGrupo $resultados,
        private PagosDeMatriculas $pagos,
    ) {}

    public function __invoke(FiltrosDeReporte $filtros): ReporteGeneral
    {
        $resultados = $this->resultados->de($filtros);
        $pagos = collect($this->pagos->de(array_map(fn (ResultadoDeGrupo $fila): int => $fila->grupo->grupoId, $resultados)));
        $porSeccion = collect($resultados)
            ->sortBy(fn (ResultadoDeGrupo $fila): array => [$fila->grupo->idioma, $fila->programa, $fila->grupo->nivel->orden(), $fila->grupo->cicloNumero, $fila->docente])
            ->groupBy(fn (ResultadoDeGrupo $fila): string => "{$fila->grupo->idioma} · {$fila->programa}");

        return new ReporteGeneral(
            'Reporte administrativo por idioma',
            $this->alcance->periodo($filtros),
            $this->alcance->descripcion($filtros, array_map(fn (ResultadoDeGrupo $fila): HorarioDeGrupo => $fila->grupo, $resultados)),
            array_values($porSeccion->map(fn (Collection $filas, string $titulo): SeccionDeReporte => $this->seccion($titulo, array_values($filas->all()), $pagos))->all()),
            formal: true,
        );
    }

    /**
     * @param  list<ResultadoDeGrupo>  $filas
     * @param  Collection<int, stdClass>  $pagos
     */
    private function seccion(string $titulo, array $filas, Collection $pagos): SeccionDeReporte
    {
        $ids = array_map(fn (ResultadoDeGrupo $fila): int => $fila->grupo->grupoId, $filas);
        $deLaSeccion = $pagos->whereIn('grupo_id', $ids);
        $vinculos = $this->valores($deLaSeccion, 'vinculo');
        $lugares = $this->valores($deLaSeccion, 'lugar');
        $cuenta = fn (Collection $matriculas): array => [
            ...array_map(fn (string $vinculo): int => $matriculas->where('vinculo', $vinculo)->count(), $vinculos),
            ...array_map(fn (string $lugar): int => $matriculas->where('lugar', $lugar)->count(), $lugares),
            $matriculas->count(),
        ];

        return new SeccionDeReporte($titulo, [
            new ColumnaDeReporte('Nivel'), new ColumnaDeReporte('Ciclo', TipoDeColumna::Entero), new ColumnaDeReporte('Docente'), new ColumnaDeReporte('Grupo'),
            ...array_map(fn (string $vinculo): ColumnaDeReporte => new ColumnaDeReporte("Vínculo · {$vinculo}", TipoDeColumna::Entero, 'Vínculo', $vinculo), $vinculos),
            ...array_map(fn (string $lugar): ColumnaDeReporte => new ColumnaDeReporte("Lugar de pago · {$lugar}", TipoDeColumna::Entero, 'Lugar de pago', $lugar), $lugares),
            new ColumnaDeReporte('Total', TipoDeColumna::Entero),
        ], array_map(fn (ResultadoDeGrupo $fila): array => [
            $fila->grupo->nivel->etiqueta(), $fila->grupo->cicloNumero, $fila->docente, $fila->grupo->codigo,
            ...$cuenta($deLaSeccion->where('grupo_id', $fila->grupo->grupoId)),
        ], $filas), [['Total', null, null, null, ...$cuenta($deLaSeccion)]], 'Idioma - Modalidad: '.str_replace(' · ', ' - ', $titulo));
    }

    // Values found in the section, with «Sin pago» last.
    /**
     * @param  Collection<int, stdClass>  $pagos
     * @return list<string>
     */
    private function valores(Collection $pagos, string $campo): array
    {
        return array_values($pagos->pluck($campo)->unique()->sortBy(fn (string $valor): array => [$valor === PagosDeMatriculas::SIN_PAGO, $valor])->all());
    }
}
