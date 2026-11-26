<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Actions;

use App\Modules\Pagos\Contracts\MediosDePago;
use App\Modules\Pagos\Data\MedioDePagoDelCatalogo;
use App\Modules\Pagos\Models\MedioDePago;

final readonly class ListarMediosDePago implements MediosDePago
{
    public function __invoke(): array
    {
        return array_values(MedioDePago::query()->activos()->get()
            ->map(fn (MedioDePago $medio): MedioDePagoDelCatalogo => new MedioDePagoDelCatalogo($medio->codigo, $medio->nombre))
            ->all());
    }
}
