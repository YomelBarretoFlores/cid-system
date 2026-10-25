<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers;

use App\Modules\Certificacion\Actions\GenerarDocumentoPdf;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use Spatie\LaravelPdf\PdfBuilder;

// A student downloads only their own valid documents; anything else answers 404, as if it did not exist.
final readonly class DescargarDocumentoDelEstudiante
{
    public function __invoke(string $documento, FichaDelUsuario $fichaDelUsuario, GenerarDocumentoPdf $generar): PdfBuilder
    {
        $personaId = $fichaDelUsuario((int) auth()->id())?->personaId;
        $id = Documento::query()->whereKey((int) $documento)->where('persona_id', $personaId)->value('id') ?? abort(404);

        try {
            return $generar((int) $id);
        } catch (ReglaDeNegocioIncumplida) {
            abort(404);
        }
    }
}
