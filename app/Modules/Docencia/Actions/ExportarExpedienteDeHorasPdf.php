<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Data\ExpedienteDeHoras;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class ExportarExpedienteDeHorasPdf
{
    public function __invoke(ExpedienteDeHoras $expediente): PdfBuilder
    {
        return Pdf::view('docencia::pdf.expediente-de-horas', ['expediente' => $expediente])
            ->format('a4')
            ->download($expediente->nombreDeArchivo('pdf'));
    }
}
