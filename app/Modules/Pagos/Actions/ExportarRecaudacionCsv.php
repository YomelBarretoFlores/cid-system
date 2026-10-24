<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Data\DetalleDeRecaudacion;
use App\Modules\Pagos\Data\ReporteDeRecaudacion;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ExportarRecaudacionCsv
{
    private const array ENCABEZADO = ['Fecha de pago', 'Documento', 'Estudiante', 'Concepto', 'Idioma', 'Nivel', 'Periodo', 'Medio de pago', 'N.° de operación', 'Monto (S/)'];

    public function __invoke(ReporteDeRecaudacion $reporte): StreamedResponse
    {
        return response()->streamDownload(function () use ($reporte): void {
            $salida = fopen('php://output', 'w');

            if ($salida === false) {
                return;
            }

            // The BOM lets spreadsheet programs read the accents as UTF-8.
            fwrite($salida, "\u{FEFF}");
            fputcsv($salida, self::ENCABEZADO, escape: '');

            foreach ($reporte->detalle as $fila) {
                fputcsv($salida, array_map($this->celdaSegura(...), $this->fila($fila)), escape: '');
            }

            fputcsv($salida, ['Total', '', '', '', '', '', '', '', '', ReporteDeRecaudacion::decimal($reporte->totalCentimos)], escape: '');
            fclose($salida);
        }, $reporte->nombreDeArchivo('csv'), ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return list<string>
     */
    private function fila(DetalleDeRecaudacion $fila): array
    {
        return [
            $fila->fechaPago->format('d/m/Y'), $fila->documento, $fila->estudiante, $fila->concepto, $fila->idioma, $fila->nivel,
            $fila->periodo, $fila->medioPago, $fila->numeroOperacion, ReporteDeRecaudacion::decimal($fila->centimos),
        ];
    }

    // A leading = + - @ would run as a formula in a spreadsheet (CSV injection); the quote keeps it as text.
    private function celdaSegura(string $valor): string
    {
        return preg_match('/^[=+\-@\t\r]/', $valor) === 1 ? "'{$valor}" : $valor;
    }
}
