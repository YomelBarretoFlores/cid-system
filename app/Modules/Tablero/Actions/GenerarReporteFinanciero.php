<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Personas\Contracts\FichasDePersonas;
use App\Modules\Personas\Data\FichaDeEstudiante;
use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Data\ResultadoDeGrupo;
use App\Modules\Tablero\Data\SeccionDeReporte;
use App\Modules\Tablero\Enums\TipoDeColumna;
use App\Modules\Tablero\Support\AlcanceDelReporte;
use App\Modules\Tablero\Support\PagosDeMatriculas;
use App\Modules\Tablero\Support\ResultadosPorGrupo;
use stdClass;

// The previous system's financial report: each enrollment payment of the period by language, program and level, with the section total.
final readonly class GenerarReporteFinanciero
{
    public function __construct(
        private AlcanceDelReporte $alcance,
        private ResultadosPorGrupo $resultados,
        private PagosDeMatriculas $pagos,
        private FichasDePersonas $fichasDePersonas,
    ) {}

    /**
     * @return array{seccion: string, orden: list<string|int>, fila: list<string|float>}|array{}
     */
    private function fila(stdClass $pago, ?ResultadoDeGrupo $grupo, ?FichaDeEstudiante $ficha): array
    {
        if (! $grupo instanceof ResultadoDeGrupo) {
            return [];
        }

        return [
            'seccion' => "{$grupo->grupo->idioma} · {$grupo->programa} · {$grupo->grupo->nivel->etiqueta()}",
            'orden' => [$grupo->grupo->idioma, $grupo->programa, $grupo->grupo->nivel->orden()],
            'fila' => [$ficha instanceof FichaDeEstudiante ? $ficha->nombreCompleto : '—', $grupo->grupo->codigo, $pago->vinculo, $pago->lugar, (string) $pago->numero_operacion, (float) $pago->monto],
        ];
    }

    public function __invoke(FiltrosDeReporte $filtros): ReporteGeneral
    {
        $resultados = collect($this->resultados->de($filtros))->keyBy(fn (ResultadoDeGrupo $fila): int => $fila->grupo->grupoId);
        $pagos = collect($this->pagos->de(array_values($resultados->keys()->all())))->filter(fn (stdClass $pago): bool => $pago->pago_id !== null);
        $fichas = ($this->fichasDePersonas)(array_values($pagos->pluck('persona_id')->map(fn (mixed $id): int => (int) $id)->unique()->all()));
        $filas = $pagos->map(fn (stdClass $pago): array => $this->fila($pago, $resultados->get((int) $pago->grupo_id), $fichas[(int) $pago->persona_id] ?? null))
            ->filter(fn (array $fila): bool => $fila !== []);

        return new ReporteGeneral(
            'Reporte financiero por idioma',
            $this->alcance->periodo($filtros),
            $this->alcance->descripcion($filtros, array_values($resultados->map(fn (ResultadoDeGrupo $fila): HorarioDeGrupo => $fila->grupo)->all())),
            array_values($filas->sortBy(fn (array $fila): array => [...$fila['orden'], $fila['fila'][0]])->groupBy('seccion')->map(fn ($deLaSeccion, string $titulo): SeccionDeReporte => new SeccionDeReporte(
                $titulo,
                [
                    new ColumnaDeReporte('Apellidos y nombres'), new ColumnaDeReporte('Grupo'), new ColumnaDeReporte('Vínculo'), new ColumnaDeReporte('Lugar de pago'),
                    new ColumnaDeReporte('N.° de comprobante'), new ColumnaDeReporte('Monto (S/)', TipoDeColumna::Decimal),
                ],
                array_values($deLaSeccion->pluck('fila')->all()),
                [['Total', null, null, null, null, round((float) $deLaSeccion->sum(fn (array $fila): float => (float) $fila['fila'][5]), 2)]],
                'Idioma - Modalidad - Nivel: '.str_replace(' · ', ' - ', $titulo),
            ))->all()),
            formal: true,
        );
    }
}
