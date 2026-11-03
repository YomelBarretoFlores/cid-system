<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Actas\Pages;

use App\Modules\Certificacion\Filament\Pages\ActasPendientes;
use App\Modules\Certificacion\Filament\Resources\Actas\ActaResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;

final class ListActas extends ListRecords
{
    protected static string $resource = ActaResource::class;

    protected ?string $subheading = 'Actas validadas por los docentes; se corrigen solo con una rectificación autorizada.';

    protected function getHeaderActions(): array
    {
        return [
            Action::make('pendientes')
                ->label('Actas pendientes')
                ->icon(Heroicon::OutlinedClock)
                ->color('gray')
                ->visible(fn (): bool => ActasPendientes::canAccess())
                ->url(ActasPendientes::getUrl()),
        ];
    }
}
