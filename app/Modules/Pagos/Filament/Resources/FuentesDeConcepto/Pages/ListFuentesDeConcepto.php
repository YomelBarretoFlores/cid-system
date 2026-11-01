<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\Pages;

use App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\FuenteDeConceptoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListFuentesDeConcepto extends ListRecords
{
    protected static string $resource = FuenteDeConceptoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nueva fuente'),
        ];
    }
}
