<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Http\Controllers;

use App\Modules\Certificacion\Contracts\RecordDeNotas;
use App\Modules\Certificacion\Data\RecordDelIdioma;
use App\Modules\Matricula\Support\ReporteDelEstudiante;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Support\Str;
use Spatie\LaravelPdf\PdfBuilder;

final class DescargarRecordDeNotas
{
    public function __invoke(FichaDelUsuario $fichaDelUsuario, RecordDeNotas $recordDeNotas, ?string $idioma = null): PdfBuilder
    {
        $personaId = ($fichaDelUsuario((int) auth()->id()) ?? abort(403))->personaId;
        $records = array_values(array_filter(
            $recordDeNotas($personaId),
            fn (RecordDelIdioma $record): bool => $idioma === null || Str::slug($record->idioma) === $idioma,
        ));

        abort_if($records === [], 404);

        return ReporteDelEstudiante::pdf('record-de-notas', $idioma === null ? 'record-de-notas' : "record-de-notas-{$idioma}", ['records' => $records]);
    }
}
