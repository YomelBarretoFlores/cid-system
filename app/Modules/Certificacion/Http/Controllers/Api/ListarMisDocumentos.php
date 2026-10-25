<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Controllers\Api;

use App\Modules\Certificacion\Actions\ListarDocumentosDeEstudiante;
use App\Modules\Certificacion\Http\Resources\DocumentoDelEstudianteResource;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListarMisDocumentos
{
    public function __construct(
        private FichaDelUsuario $fichaDelUsuario,
        private ListarDocumentosDeEstudiante $listarDocumentos,
    ) {}

    /**
     * The documents issued to the student, newest first, with their public verification link.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $ficha = ($this->fichaDelUsuario)((int) $request->user()?->getAuthIdentifier()) ?? abort(403);

        return DocumentoDelEstudianteResource::collection(($this->listarDocumentos)($ficha->personaId));
    }
}
