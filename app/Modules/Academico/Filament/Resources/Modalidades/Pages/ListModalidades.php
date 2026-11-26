<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Modalidades\Pages;

use App\Modules\Academico\Filament\Resources\Modalidades\ModalidadResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListModalidades extends ListRecords
{
    #[\Override]
    protected static string $resource = ModalidadResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva modalidad'),
        ];
    }
}
