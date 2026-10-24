<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Matricula\Actions\BuscarMatriculaDelEstudiante;
use App\Modules\Matricula\Actions\ComponerDocumentoDeMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use Carbon\CarbonImmutable;
use Illuminate\Http\RedirectResponse;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

final class DescargarConstanciaDeInscripcion
{
    public function __invoke(string $matricula, BuscarMatriculaDelEstudiante $buscar, ComponerDocumentoDeMatricula $componer): PdfBuilder|RedirectResponse
    {
        $registro = $buscar($matricula, (int) auth()->id());

        if ($registro->estado !== EstadoMatricula::Activa) {
            return to_route('estudiante.matriculas')->with('error', 'La constancia de inscripción solo se emite para matrículas activas.');
        }

        $documento = $componer($registro);

        return Pdf::view('matricula::pdf.constancia', [
            'documento' => $documento,
            'emitidaEl' => CarbonImmutable::now('America/Lima')->settings(['locale' => 'es'])->translatedFormat('j \\d\\e F \\d\\e Y'),
        ])
            ->format('a4')
            ->inline("constancia-de-inscripcion-{$documento->grupo->codigo}.pdf");
    }
}
