<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Matricula\Actions\BuscarMatriculaDelEstudiante;
use App\Modules\Matricula\Actions\ComponerDocumentoDeMatricula;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final class DescargarFichaDeMatricula
{
    public function __invoke(string $matricula, BuscarMatriculaDelEstudiante $buscar, ComponerDocumentoDeMatricula $componer): PdfBuilder
    {
        $documento = $componer($buscar($matricula, (int) auth()->id()));

        return Pdf::view('matricula::pdf.ficha', ['documento' => $documento])
            ->format('a4')
            ->inline("ficha-de-matricula-{$documento->grupo->codigo}.pdf");
    }
}
