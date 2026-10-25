<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Http\Controllers\Api;

use App\Modules\Personas\Contracts\FichaDelUsuario;
use App\Modules\Tramites\Actions\ListarTramitesDeEstudiante;
use App\Modules\Tramites\Http\Resources\TramiteDelEstudianteResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListarMisTramites
{
    public function __construct(
        private FichaDelUsuario $fichaDelUsuario,
        private ListarTramitesDeEstudiante $listarTramites,
    ) {}

    /**
     * The student's own trámites, newest first, with their current stage, progress and any observation.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $ficha = ($this->fichaDelUsuario)((int) $request->user()?->getAuthIdentifier()) ?? abort(403);

        return TramiteDelEstudianteResource::collection(($this->listarTramites)($ficha->personaId));
    }
}
