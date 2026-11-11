<?php

declare(strict_types=1);

namespace App\Shared\Auth\Filament\Resources\Usuarios\Pages;

use App\Shared\Auth\Filament\Resources\Usuarios\UsuarioResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListUsuarios extends ListRecords
{
    #[\Override]
    protected static string $resource = UsuarioResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo usuario'),
        ];
    }
}
