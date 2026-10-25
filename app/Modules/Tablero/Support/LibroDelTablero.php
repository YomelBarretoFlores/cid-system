<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Support;

use App\Modules\Tablero\Data\FilaDeDesercion;
use App\Modules\Tablero\Data\FilaDeMatriculas;
use App\Modules\Tablero\Data\FilaDeRecaudacion;
use App\Modules\Tablero\Data\FilaDeRentabilidad;
use App\Modules\Tablero\Data\FranjaHoraria;
use App\Modules\Tablero\Data\PagoPorValidar;
use App\Modules\Tablero\Data\PuntoDeMatriculas;
use App\Modules\Tablero\Data\Tablero;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

// One sheet per indicator; amounts are numbers in soles so the spreadsheet can add them up.
final readonly class LibroDelTablero implements Export, WithMultipleSheets
{
    private const string SOLES = '#,##0.00';

    private const string PORCENTAJE = '0.0';

    public function __construct(private Tablero $tablero) {}

    /**
     * @return list<HojaDelTablero>
     */
    public function sheets(): array
    {
        $tablero = $this->tablero;

        return [
            new HojaDelTablero('Resumen', ['Indicador', 'Valor'], $this->resumen()),
            new HojaDelTablero('Recaudación', ['Idioma', 'Nivel', 'Pagos', 'Monto (S/)'], array_map(
                fn (FilaDeRecaudacion $fila): array => [$fila->idioma, $fila->nivel, $fila->operaciones, $fila->centimos / 100],
                $tablero->recaudacion->filas,
            ), ['D' => self::SOLES]),
            new HojaDelTablero('Por validar', ['Concepto', 'Monto (S/)', 'Fecha de pago', 'Días de espera'], array_map(
                fn (PagoPorValidar $pago): array => [$pago->concepto, $pago->centimos / 100, $pago->fechaPago->toDateString(), $pago->dias],
                $tablero->pagosPorValidar->masAntiguos,
            ), ['B' => self::SOLES]),
            new HojaDelTablero('Rentabilidad', ['Grupo', 'Idioma', 'Nivel', 'Matrículas', 'Ingresos (S/)', 'Horas dictadas', 'Costo docente (S/)', 'Margen (S/)', 'Horas sin costo vigente'], array_map(
                fn (FilaDeRentabilidad $fila): array => [$fila->codigo, $fila->idioma, $fila->nivel, $fila->matriculas, $fila->ingresosCentimos / 100, $fila->horasCentesimas / 100, $fila->costoCentimos / 100, $fila->margen() / 100, $fila->horasSinCostoCentesimas / 100],
                $tablero->rentabilidad->filas,
            ), ['E' => self::SOLES, 'F' => '0.00', 'G' => self::SOLES, 'H' => self::SOLES, 'I' => '0.00']),
            new HojaDelTablero('Matrículas', ['Periodo o idioma', 'Matrículas'], [
                ...array_map(fn (PuntoDeMatriculas $punto): array => [$punto->periodo, $punto->total], $tablero->matriculas->serie),
                ...array_map(fn (FilaDeMatriculas $fila): array => ["{$tablero->periodo} · {$fila->idioma}", $fila->total], $tablero->matriculas->porIdioma),
            ]),
            new HojaDelTablero('Deserción', ['Nivel o idioma', 'Matrículas', 'Retiros', 'Tasa (%)'], array_map(
                fn (FilaDeDesercion $fila): array => [$fila->etiqueta, $fila->matriculas, $fila->retiradas, $fila->tasa()],
                [...$tablero->desercion->porNivel, ...$tablero->desercion->porIdioma, $tablero->desercion->total],
            ), ['D' => self::PORCENTAJE]),
            new HojaDelTablero('Demanda horaria', ['Horario', 'Días', 'Grupos', 'Matriculados', 'Vacantes', 'Ocupación (%)'], array_map(
                fn (FranjaHoraria $franja): array => ["{$franja->horaInicio} – {$franja->horaFin}", DiasDeClase::texto($franja->dias), $franja->grupos, $franja->matriculados, $franja->cupo, $franja->ocupacion()],
                $tablero->demandaHoraria,
            ), ['F' => self::PORCENTAJE]),
        ];
    }

    /**
     * @return list<list<string>>
     */
    private function resumen(): array
    {
        $tablero = $this->tablero;
        $pagos = $tablero->pagosPorValidar;
        $matriculas = $tablero->matriculas;

        return [
            ['Alcance', $tablero->alcance()],
            ['Recaudación', Cifras::soles($tablero->recaudacion->totalCentimos)],
            ['Pagos validados', (string) $tablero->recaudacion->operaciones],
            ['Pagos por validar (todos los periodos)', (string) $pagos->porValidar],
            ['Monto por validar', Cifras::soles($pagos->centimosPorValidar)],
            ['Tasa de pagos pendientes de validación del periodo', Cifras::porcentaje($pagos->tasa())],
            ['Matrículas', (string) $matriculas->total],
            ['Crecimiento frente al periodo anterior', Cifras::variacion($matriculas->crecimientoMensual())],
            ['Crecimiento frente al mismo periodo del año anterior', Cifras::variacion($matriculas->crecimientoAnual())],
            ['Deserción', Cifras::porcentaje($tablero->desercion->total->tasa())],
            ['Margen de los grupos', Cifras::soles($tablero->rentabilidad->margen())],
        ];
    }
}
