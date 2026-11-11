<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\Pages;

use App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\FuenteDeConceptoResource;
use Filament\Resources\Pages\EditRecord;

final class EditFuenteDeConcepto extends EditRecord
{
    #[\Override]
    protected static string $resource = FuenteDeConceptoResource::class;
}
