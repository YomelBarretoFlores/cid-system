<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\Pages;

use App\Modules\Certificacion\Filament\Resources\DenominacionesDelAnio\DenominacionDelAnioResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateDenominacionDelAnio extends CreateRecord
{
    protected static string $resource = DenominacionDelAnioResource::class;
}
