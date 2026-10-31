<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Docencia\Contracts\AsistenciaDeMatricula;
use App\Modules\Matricula\Actions\BuscarMatriculaDelEstudiante;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Support\ReporteDelEstudiante;
use Spatie\LaravelPdf\PdfBuilder;

final class DescargarReporteDeAsistencia
{
    public function __invoke(string $matricula, BuscarMatriculaDelEstudiante $buscar, DetalleDeGrupos $detalleDeGrupos, AsistenciaDeMatricula $asistenciaDeMatricula): PdfBuilder
    {
        $registro = $buscar($matricula, (int) auth()->id());
        abort_if($registro->estado !== EstadoMatricula::Activa, 404);
        $grupo = $detalleDeGrupos([$registro->grupo_id])[$registro->grupo_id] ?? abort(404);

        return ReporteDelEstudiante::pdf('asistencia', "asistencia-{$grupo->codigo}", [
            'grupo' => $grupo,
            'asistencia' => $asistenciaDeMatricula($registro->id, $registro->grupo_id),
        ]);
    }
}
