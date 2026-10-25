<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Actions;

use App\Modules\Tablero\Data\Tablero;
use Spatie\LaravelPdf\Enums\Orientation;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class ExportarTableroPdf
{
    public function __invoke(Tablero $tablero): PdfBuilder
    {
        return Pdf::view('tablero::pdf.tablero', ['tablero' => $tablero])
            ->format('a4')
            ->orientation(Orientation::Landscape)
            ->download($tablero->nombreDeArchivo('pdf'));
    }
}
