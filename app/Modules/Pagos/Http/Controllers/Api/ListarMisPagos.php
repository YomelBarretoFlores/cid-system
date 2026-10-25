<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Http\Controllers\Api;

use App\Modules\Pagos\Actions\ListarPagosDeEstudiante;
use App\Modules\Pagos\Http\Resources\PagoDelEstudianteResource;
use App\Modules\Personas\Contracts\FichaDelUsuario;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListarMisPagos
{
    public function __construct(
        private FichaDelUsuario $fichaDelUsuario,
        private ListarPagosDeEstudiante $listarPagos,
    ) {}

    /**
     * The student's own payments, newest first, with their state and the reason when one was rejected.
     */
    public function __invoke(Request $request): AnonymousResourceCollection
    {
        $ficha = ($this->fichaDelUsuario)((int) $request->user()?->getAuthIdentifier()) ?? abort(403);

        return PagoDelEstudianteResource::collection(($this->listarPagos)($ficha->personaId));
    }
}
