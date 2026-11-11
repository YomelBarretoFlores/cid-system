<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Idiomas\Pages;

use App\Modules\Academico\Filament\Resources\Idiomas\IdiomaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListIdiomas extends ListRecords
{
    #[\Override]
    protected static string $resource = IdiomaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo idioma'),
        ];
    }
}
