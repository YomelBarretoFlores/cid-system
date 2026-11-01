<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages;

use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\CondicionLaboralResource;
use Filament\Resources\Pages\EditRecord;

final class EditCondicionLaboral extends EditRecord
{
    protected static string $resource = CondicionLaboralResource::class;
}
