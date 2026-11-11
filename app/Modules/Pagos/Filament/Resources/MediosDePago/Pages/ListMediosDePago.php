<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\MediosDePago\Pages;

use App\Modules\Pagos\Filament\Resources\MediosDePago\MedioDePagoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListMediosDePago extends ListRecords
{
    #[\Override]
    protected static string $resource = MedioDePagoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo medio de pago'),
        ];
    }
}
