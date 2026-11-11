<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\Pages;

use App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\DenominacionDelAnioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListDenominacionesDelAnio extends ListRecords
{
    #[\Override]
    protected static string $resource = DenominacionDelAnioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva denominación'),
        ];
    }
}
