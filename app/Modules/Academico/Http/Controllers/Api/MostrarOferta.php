<?php

declare(strict_types=1);

namespace App\Modules\Academico\Http\Controllers\Api;

use App\Modules\Academico\Contracts\OfertaPublica;
use App\Modules\Academico\Data\OfertaDelPeriodo;
use App\Modules\Academico\Http\Resources\OfertaDelPeriodoResource;
use Illuminate\Http\JsonResponse;

final readonly class MostrarOferta
{
    public function __construct(private OfertaPublica $ofertaPublica) {}

    /**
     * The period whose enrollment is open, or the next one to open, with its groups; null when there is none.
     *
     * @unauthenticated
     */
    public function __invoke(): OfertaDelPeriodoResource|JsonResponse
    {
        $oferta = ($this->ofertaPublica)();

        return $oferta instanceof OfertaDelPeriodo ? new OfertaDelPeriodoResource($oferta) : response()->json(['data' => null]);
    }
}
