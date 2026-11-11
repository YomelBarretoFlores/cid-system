<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Periodos\Pages;

use App\Modules\Academico\Filament\Resources\Periodos\PeriodoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListPeriodos extends ListRecords
{
    #[\Override]
    protected static string $resource = PeriodoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo periodo'),
        ];
    }
}
