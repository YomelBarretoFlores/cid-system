<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Matricula\Actions\BuscarMatriculaDelEstudiante;
use App\Modules\Matricula\Actions\GenerarFichaDeMatricula;
use Spatie\LaravelPdf\PdfBuilder;

final class DescargarFichaDeMatricula
{
    public function __invoke(string $matricula, BuscarMatriculaDelEstudiante $buscar, GenerarFichaDeMatricula $generar): PdfBuilder
    {
        return $generar($buscar($matricula, (int) auth()->id()));
    }
}
