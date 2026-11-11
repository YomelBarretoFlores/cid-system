<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\MediosDePago\Pages;

use App\Modules\Pagos\Filament\Resources\MediosDePago\MedioDePagoResource;
use Filament\Resources\Pages\EditRecord;

final class EditMedioDePago extends EditRecord
{
    #[\Override]
    protected static string $resource = MedioDePagoResource::class;
}
