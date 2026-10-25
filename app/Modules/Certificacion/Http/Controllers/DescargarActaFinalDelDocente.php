<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers;

use App\Modules\Certificacion\Actions\GenerarActaFinalPdf;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Support\AccesoDelDocente;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Illuminate\Http\Request;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class DescargarActaFinalDelDocente
{
    public function __invoke(Request $request, int $grupo, AccesoDelDocente $acceso, GenerarActaFinalPdf $generar): PdfBuilder
    {
        $acceso->asegurar((int) $request->user()?->id, $grupo);
        $actaId = Acta::query()->where('grupo_id', $grupo)->value('id') ?? abort(404);

        try {
            return $generar($actaId);
        } catch (ReglaDeNegocioIncumplida) {
            abort(404);
        }
    }
}
