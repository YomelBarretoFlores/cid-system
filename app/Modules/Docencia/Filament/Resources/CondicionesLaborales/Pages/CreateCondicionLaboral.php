<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages;

use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\CondicionLaboralResource;
use Filament\Resources\Pages\CreateRecord;

final class CreateCondicionLaboral extends CreateRecord
{
    protected static string $resource = CondicionLaboralResource::class;

    protected function getRedirectUrl(): string
    {
        return CondicionLaboralResource::getUrl('index');
    }
}
