<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Actions;

use App\Modules\Docencia\Contracts\SilaboEnPdf;
use App\Modules\Docencia\Data\SilaboParaLeer;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class GenerarSilaboPdf implements SilaboEnPdf
{
    public function __invoke(SilaboParaLeer $silabo): PdfBuilder
    {
        return Pdf::view('docencia::pdf.silabo', ['silabo' => $silabo])
            ->format('a4')
            ->landscape()
            ->inline("silabo-{$silabo->grupo}.pdf");
    }
}
