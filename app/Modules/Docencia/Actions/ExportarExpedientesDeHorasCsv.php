<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Data\ExpedienteDeHoras;
use App\Modules\Docencia\Data\LineaDeHoras;
use Symfony\Component\HttpFoundation\StreamedResponse;

final readonly class ExportarExpedientesDeHorasCsv
{
    private const array ENCABEZADO = ['Periodo', 'Código', 'Docente', 'Categoría', 'Idioma', 'Nivel', 'Total de horas', 'Costo por hora (S/)', 'Monto bruto referencial (S/)'];

    /**
     * @param  list<ExpedienteDeHoras>  $expedientes
     */
    public function __invoke(array $expedientes, string $nombreDeArchivo): StreamedResponse
    {
        return response()->streamDownload(function () use ($expedientes): void {
            $salida = fopen('php://output', 'w');

            if ($salida === false) {
                return;
            }

            // The BOM lets spreadsheet programs read the accents as UTF-8.
            fwrite($salida, "\u{FEFF}");
            fputcsv($salida, self::ENCABEZADO, escape: '');

            foreach ($expedientes as $expediente) {
                foreach ($expediente->lineas as $linea) {
                    fputcsv($salida, array_map($this->celdaSegura(...), $this->fila($expediente, $linea)), escape: '');
                }
            }

            $horas = array_sum(array_map(fn (ExpedienteDeHoras $expediente): int => $expediente->horasTotalesCentesimas(), $expedientes));
            $monto = array_sum(array_map(fn (ExpedienteDeHoras $expediente): int => $expediente->montoTotalCentimos(), $expedientes));
            fputcsv($salida, ['Total', '', '', '', '', '', LineaDeHoras::decimal($horas), '', LineaDeHoras::decimal($monto)], escape: '');
            fclose($salida);
        }, $nombreDeArchivo, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    /**
     * @return list<string>
     */
    private function fila(ExpedienteDeHoras $expediente, LineaDeHoras $linea): array
    {
        return [
            $expediente->periodo(), $expediente->codigo, $expediente->nombre, $expediente->categoria, $linea->idioma, $linea->nivel,
            $linea->horas(), $linea->costoPorHora() ?? 'Sin costo vigente', $linea->monto() ?? '',
        ];
    }

    // A leading = + - @, tab or carriage return would run as a formula in a spreadsheet (CSV injection); the quote keeps it as text.
    private function celdaSegura(string $valor): string
    {
        return preg_match('/^[=+\-@\t\r]/', $valor) === 1 ? "'{$valor}" : $valor;
    }
}
