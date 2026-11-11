<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Firmantes\Pages;

use App\Modules\Certificacion\Filament\Resources\Firmantes\FirmanteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListFirmantes extends ListRecords
{
    #[\Override]
    protected static string $resource = FirmanteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo firmante'),
        ];
    }
}
