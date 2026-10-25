<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Actions;

use App\Modules\Certificacion\Data\VerificacionDeDocumento;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Support\CodigoDeDocumento;

// The public verification of a document: what its QR opens on the web and what the API answers.
final readonly class VerificarDocumentoPorCodigo
{
    public function __invoke(string $codigo): ?VerificacionDeDocumento
    {
        $documento = Documento::query()->where('codigo', CodigoDeDocumento::normalizar($codigo))->first();

        return $documento === null ? null : VerificacionDeDocumento::desde($documento);
    }
}
