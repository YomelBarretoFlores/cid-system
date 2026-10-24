<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\Pages;

use App\Modules\Pagos\Filament\Resources\Conceptos\ConceptoResource;
use Filament\Resources\Pages\EditRecord;

final class EditConcepto extends EditRecord
{
    protected static string $resource = ConceptoResource::class;
}
