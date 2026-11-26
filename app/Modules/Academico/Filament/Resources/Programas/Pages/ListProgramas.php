<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Programas\Pages;

use App\Modules\Academico\Filament\Resources\Programas\ProgramaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListProgramas extends ListRecords
{
    #[\Override]
    protected static string $resource = ProgramaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo programa'),
        ];
    }
}
