<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Matricula\Actions\GenerarFichaDeMatricula;
use App\Modules\Matricula\Models\Matricula;
use Spatie\LaravelPdf\PdfBuilder;

final class DescargarFichaParaPersonal
{
    public function __invoke(Matricula $matricula, GenerarFichaDeMatricula $generar): PdfBuilder
    {
        return $generar($matricula);
    }
}
