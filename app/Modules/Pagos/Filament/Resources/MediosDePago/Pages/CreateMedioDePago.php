<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\MediosDePago\Pages;

use App\Modules\Pagos\Filament\Resources\MediosDePago\MedioDePagoResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateMedioDePago extends CreateRecord
{
    protected static string $resource = MedioDePagoResource::class;

    protected function getRedirectUrl(): string
    {
        return MedioDePagoResource::getUrl('index');
    }
}
