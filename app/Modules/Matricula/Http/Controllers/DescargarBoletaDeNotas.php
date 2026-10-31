<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Certificacion\Contracts\BoletaDeNotas;
use App\Modules\Matricula\Actions\BuscarMatriculasDelPeriodo;
use App\Modules\Matricula\Support\ReporteDelEstudiante;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\PdfBuilder;

final class DescargarBoletaDeNotas
{
    public function __invoke(string $periodo, BuscarMatriculasDelPeriodo $buscar, BoletaDeNotas $boletaDeNotas): PdfBuilder
    {
        $datos = $buscar((int) $periodo, (int) auth()->id());
        $nombre = $datos->grupos[$datos->matriculas->firstOrFail()->grupo_id]->periodo;

        return ReporteDelEstudiante::pdf('boleta-de-notas', 'boleta-de-notas-'.Str::slug($nombre), [
            'periodo' => $nombre,
            'boleta' => $boletaDeNotas($datos->grupoPorMatricula()),
        ]);
    }
}
