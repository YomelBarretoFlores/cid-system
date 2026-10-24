<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Actions;

use App\Modules\Pagos\Models\Pago;
use Filament\Actions\Action;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class AccionesDePago
{
    public static function descargarRecibo(): Action
    {
        return Action::make('descargarRecibo')
            ->label('Descargar recibo')
            ->icon(Heroicon::OutlinedArrowDownTray)
            ->color('gray')
            ->authorize('view')
            ->action(fn (Pago $record): StreamedResponse => Storage::disk('local')->download(
                $record->recibo_path,
                "recibo-{$record->numero_operacion}.".pathinfo($record->recibo_path, PATHINFO_EXTENSION),
            ));
    }
}
