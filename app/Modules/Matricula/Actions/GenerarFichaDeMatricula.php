<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Actions;

use App\Modules\Matricula\Models\Matricula;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class GenerarFichaDeMatricula
{
    public function __construct(private ComponerDocumentoDeMatricula $componer) {}

    public function __invoke(Matricula $matricula): PdfBuilder
    {
        $documento = ($this->componer)($matricula);

        return Pdf::view('matricula::pdf.ficha', ['documento' => $documento])
            ->format('a4')
            ->inline("ficha-de-matricula-{$documento->grupo->codigo}.pdf");
    }
}
