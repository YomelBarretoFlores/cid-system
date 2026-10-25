<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\CodigoDeDocumento;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;
use App\Shared\Support\Logging\RegistroDeRechazos;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Spatie\LaravelPdf\PdfBuilder;

// A student gets only their own valid documents; anything else is reported as missing, as if it did not exist.
final readonly class GenerarPdfDelEstudiante
{
    public function __construct(private GenerarDocumentoPdf $generar) {}

    public function __invoke(int $personaId, string $codigo): PdfBuilder
    {
        $id = Documento::query()->where('codigo', CodigoDeDocumento::normalizar($codigo))->where('persona_id', $personaId)->value('id')
            ?? throw (new ModelNotFoundException)->setModel(Documento::class);

        try {
            return ($this->generar)((int) $id);
        } catch (ReglaDeNegocioIncumplida $error) {
            // The student only sees a missing document; the log keeps why it was refused.
            RegistroDeRechazos::registrar($error);

            throw (new ModelNotFoundException)->setModel(Documento::class);
        }
    }
}
