<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Pages;

use App\Modules\Personas\Filament\Resources\Personas\PersonaResource;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

final class ViewPersona extends ViewRecord
{
    #[\Override]
    protected static string $resource = PersonaResource::class;

    protected function getHeaderActions(): array
    {
        return [
            EditAction::make(),
        ];
    }
}
