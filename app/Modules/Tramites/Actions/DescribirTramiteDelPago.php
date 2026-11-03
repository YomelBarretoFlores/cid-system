<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Actions;

use App\Modules\Tramites\Contracts\TramiteDelPago;
use App\Modules\Tramites\Models\Tramite;

final readonly class DescribirTramiteDelPago implements TramiteDelPago
{
    public function __invoke(int $pagoId): ?string
    {
        $tramite = Tramite::query()->where('pago_id', $pagoId)->first(['codigo', 'tipo_nombre']);

        return $tramite === null ? null : "{$tramite->codigo} ({$tramite->tipo_nombre})";
    }
}
