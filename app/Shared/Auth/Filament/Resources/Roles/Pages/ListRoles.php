<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Roles\Pages;

use App\Shared\Auth\Filament\Resources\Roles\RolResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListRoles extends ListRecords
{
    #[\Override]
    protected static string $resource = RolResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo rol'),
        ];
    }
}
