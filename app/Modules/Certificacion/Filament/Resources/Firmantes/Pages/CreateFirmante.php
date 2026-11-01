<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Firmantes\Pages;

use App\Modules\Certificacion\Filament\Resources\Firmantes\FirmanteResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateFirmante extends CreateRecord
{
    protected static string $resource = FirmanteResource::class;
}
