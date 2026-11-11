<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Aulas\Pages;

use App\Modules\Academico\Filament\Resources\Aulas\AulaResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListAulas extends ListRecords
{
    #[\Override]
    protected static string $resource = AulaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva aula'),
        ];
    }
}
