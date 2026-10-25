<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers;

use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Models\Documento;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Spatie\LaravelPdf\PdfBuilder;

final readonly class DescargarDocumentoParaPersonal
{
    public function __invoke(Documento $documento, GenerarDocumentoPdf $generar): PdfBuilder
    {
        try {
            return $generar($documento->id);
        } catch (ReglaDeNegocioIncumplida) {
            abort(404);
        }
    }
}
