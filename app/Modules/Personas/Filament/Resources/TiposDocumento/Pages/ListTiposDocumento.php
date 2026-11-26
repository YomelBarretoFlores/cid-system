<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\TiposDocumento\Pages;

use App\Modules\Personas\Filament\Resources\TiposDocumento\TipoDocumentoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTiposDocumento extends ListRecords
{
    #[\Override]
    protected static string $resource = TipoDocumentoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo tipo de documento'),
        ];
    }
}
