<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages;

use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\CondicionLaboralResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListCondicionesLaborales extends ListRecords
{
    protected static string $resource = CondicionLaboralResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva condición laboral'),
        ];
    }
}
