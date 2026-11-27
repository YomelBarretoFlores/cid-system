<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Tablero\Data\ReporteGeneral;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class ExportarReporteGeneralPdf
{
    public function __invoke(ReporteGeneral $reporte): PdfBuilder
    {
        $pdf = Pdf::view($reporte->formal ? 'tablero::pdf.reporte-formal' : 'tablero::pdf.reporte-general', [
            'reporte' => $reporte,
            'emitido' => now('America/Lima')->format('Y-m-d H:i:s'),
        ])->format('a4');

        return ($reporte->formal ? $pdf->portrait() : $pdf->landscape())->download($reporte->nombreDeArchivo('pdf'));
    }
}
