<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\Pages;

use App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\FuenteDeConceptoResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFuenteDeConcepto extends CreateRecord
{
    #[\Override]
    protected static string $resource = FuenteDeConceptoResource::class;

    protected function getRedirectUrl(): string
    {
        return FuenteDeConceptoResource::getUrl('index');
    }
}
