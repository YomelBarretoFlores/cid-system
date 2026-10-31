<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Matricula\Actions\ArmarHorarioSemanal;
use App\Modules\Matricula\Actions\BuscarMatriculasDelPeriodo;
use App\Modules\Matricula\Support\ReporteDelEstudiante;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\PdfBuilder;

// The same template answers as PDF or, on the print route, as a printable page.
final class DescargarHorarioSemanal
{
    public function __invoke(Request $request, string $periodo, BuscarMatriculasDelPeriodo $buscar, ArmarHorarioSemanal $armarHorario): PdfBuilder|View
    {
        $datos = $buscar((int) $periodo, (int) auth()->id());
        $nombre = $datos->grupos[$datos->matriculas->firstOrFail()->grupo_id]->periodo;
        $vista = ['periodo' => $nombre, 'horario' => $armarHorario($datos)];

        if ($request->routeIs('estudiante.reportes.horario.imprimir')) {
            return view('matricula::pdf.reportes.horario-semanal', ReporteDelEstudiante::datos([...$vista, 'imprimible' => true]));
        }

        return ReporteDelEstudiante::pdf('horario-semanal', 'horario-'.Str::slug($nombre), $vista);
    }
}
