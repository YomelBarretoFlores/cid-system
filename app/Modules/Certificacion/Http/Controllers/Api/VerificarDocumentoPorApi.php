<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers\Api;

use App\Modules\Certificacion\Actions\VerificarDocumentoPorCodigo;
use App\Modules\Certificacion\Data\VerificacionDeDocumento;
use App\Modules\Certificacion\Http\Resources\VerificacionDeDocumentoResource;
use Illuminate\Http\JsonResponse;

final readonly class VerificarDocumentoPorApi
{
    public function __construct(private VerificarDocumentoPorCodigo $verificar) {}

    /**
     * Verify a document by the code printed under its QR, with the minimal data of the public page.
     *
     * @unauthenticated
     */
    public function __invoke(string $codigo): JsonResponse
    {
        $verificacion = ($this->verificar)($codigo);
        $respuesta = $verificacion instanceof VerificacionDeDocumento
            ? new VerificacionDeDocumentoResource($verificacion)->response()
            : response()->json(['message' => 'No existe un documento con ese código.'], 404);

        return $respuesta->header('X-Robots-Tag', 'noindex, nofollow');
    }
}
