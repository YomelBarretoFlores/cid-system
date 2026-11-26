<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\GradosFut\Pages;

use App\Modules\Tramites\Filament\Resources\GradosFut\GradoFutResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListGradosFut extends ListRecords
{
    #[\Override]
    protected static string $resource = GradoFutResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo grado'),
        ];
    }
}
