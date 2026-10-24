<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\Pages;

use App\Modules\Pagos\Filament\Resources\Conceptos\ConceptoResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateConcepto extends CreateRecord
{
    protected static string $resource = ConceptoResource::class;

    protected function getRedirectUrl(): string
    {
        return ConceptoResource::getUrl('edit', ['record' => $this->getRecord()]);
    }
}
