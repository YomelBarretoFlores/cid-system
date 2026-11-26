<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\TiposVinculo\Pages;

use App\Modules\Personas\Filament\Resources\TiposVinculo\TipoVinculoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTiposVinculo extends ListRecords
{
    #[\Override]
    protected static string $resource = TipoVinculoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo tipo de vínculo'),
        ];
    }
}
