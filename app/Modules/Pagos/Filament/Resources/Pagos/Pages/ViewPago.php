<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Pages;

use App\Modules\Pagos\Filament\Resources\Pagos\Actions\AccionesDePago;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use Filament\Resources\Pages\ViewRecord;

final class ViewPago extends ViewRecord
{
    protected static string $resource = PagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            AccionesDePago::descargarRecibo(),
        ];
    }
}
