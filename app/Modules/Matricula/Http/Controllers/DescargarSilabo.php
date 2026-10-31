<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Docencia\Contracts\LecturaDelSilabo;
use App\Modules\Docencia\Contracts\SilaboEnPdf;
use App\Modules\Docencia\Data\SilaboParaLeer;
use App\Modules\Matricula\Actions\BuscarMatriculaDelEstudiante;
use App\Modules\Matricula\Enums\EstadoMatricula;
use Spatie\LaravelPdf\PdfBuilder;

final class DescargarSilabo
{
    public function __invoke(string $matricula, BuscarMatriculaDelEstudiante $buscar, LecturaDelSilabo $lecturaDelSilabo, SilaboEnPdf $silaboEnPdf): PdfBuilder
    {
        $registro = $buscar($matricula, (int) auth()->id());
        abort_if($registro->estado !== EstadoMatricula::Activa, 404);
        $silabo = $lecturaDelSilabo($registro->grupo_id);
        abort_unless($silabo instanceof SilaboParaLeer && $silabo->estaPublicado(), 404);

        return $silaboEnPdf($silabo);
    }
}
