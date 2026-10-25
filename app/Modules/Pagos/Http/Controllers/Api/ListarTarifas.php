<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Http\Controllers\Api;

use App\Modules\Pagos\Contracts\TarifasVigentes;
use App\Modules\Pagos\Http\Resources\TarifaVigenteResource;
use Carbon\CarbonImmutable;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

final readonly class ListarTarifas
{
    public function __construct(private TarifasVigentes $tarifasVigentes) {}

    /**
     * TUPA/TUSNE fees in force today in Lima, by link to the university.
     *
     * @unauthenticated
     */
    public function __invoke(): AnonymousResourceCollection
    {
        return TarifaVigenteResource::collection(($this->tarifasVigentes)(CarbonImmutable::today('America/Lima')));
    }
}
