<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Data\ReporteDeRecaudacion;
use Spatie\LaravelPdf\Enums\Orientation;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class ExportarRecaudacionPdf
{
    public function __invoke(ReporteDeRecaudacion $reporte): PdfBuilder
    {
        return Pdf::view('pagos::pdf.recaudacion', ['reporte' => $reporte])
            ->format('a4')
            ->orientation(Orientation::Landscape)
            ->download($reporte->nombreDeArchivo('pdf'));
    }
}
