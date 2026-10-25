<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers;

use App\Modules\Certificacion\Actions\GenerarActaFinalPdf;
use App\Modules\Certificacion\Models\Acta;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class DescargarActaFinalParaPersonal
{
    public function __invoke(Acta $acta, GenerarActaFinalPdf $generar): PdfBuilder
    {
        try {
            return $generar($acta->id);
        } catch (ReglaDeNegocioIncumplida) {
            abort(404);
        }
    }
}
