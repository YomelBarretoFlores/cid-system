<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\Pages;

use App\Modules\Pagos\Filament\Resources\Conceptos\ConceptoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListConceptos extends ListRecords
{
    #[\Override]
    protected static string $resource = ConceptoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo concepto'),
        ];
    }
}
