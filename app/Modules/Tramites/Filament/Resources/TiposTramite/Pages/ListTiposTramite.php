<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\TiposTramite\Pages;

use App\Modules\Tramites\Filament\Resources\TiposTramite\TipoTramiteResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListTiposTramite extends ListRecords
{
    #[\Override]
    protected static string $resource = TipoTramiteResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
