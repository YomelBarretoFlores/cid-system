<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Academico\Contracts\DetalleDeGrupos;
use App\Modules\Certificacion\Contracts\AvanceDeNotas;
use App\Modules\Matricula\Actions\BuscarMatriculaDelEstudiante;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Support\ReporteDelEstudiante;
use Spatie\LaravelPdf\PdfBuilder;

final class DescargarAvanceDeNotas
{
    public function __invoke(string $matricula, BuscarMatriculaDelEstudiante $buscar, DetalleDeGrupos $detalleDeGrupos, AvanceDeNotas $avanceDeNotas): PdfBuilder
    {
        $registro = $buscar($matricula, (int) auth()->id());
        abort_if($registro->estado !== EstadoMatricula::Activa, 404);
        $grupo = $detalleDeGrupos([$registro->grupo_id])[$registro->grupo_id] ?? abort(404);

        return ReporteDelEstudiante::pdf('avance-de-notas', "avance-de-notas-{$grupo->codigo}", [
            'grupo' => $grupo,
            'avance' => $avanceDeNotas($registro->grupo_id, $registro->id),
        ]);
    }
}
