<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Bancos\Pages;

use App\Modules\Docencia\Filament\Resources\Bancos\BancoResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

final class ListBancos extends ListRecords
{
    #[\Override]
    protected static string $resource = BancoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()->label('Nuevo banco'),
        ];
    }
}
