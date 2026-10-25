<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Actas\Actions;

use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use App\Shared\Auth\Models\User;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Textarea;
use Filament\Support\Icons\Heroicon;

final class AccionesDeActa
{
    public static function descargarPdf(): Action
    {
        return Action::make('descargarPdf')
            ->label('Acta en PDF')
            ->icon(Heroicon::OutlinedDocumentArrowDown)
            ->color('gray')
            ->authorize('view')
            ->visible(fn (Acta $record): bool => $record->estado === EstadoActa::Cerrada)
            ->url(fn (Acta $record): string => route('panel.actas.pdf', $record))
            ->openUrlInNewTab();
    }

    public static function autorizarRectificacion(): Action
    {
        return Action::make('autorizarRectificacion')
            ->label('Autorizar rectificación')
            ->icon(Heroicon::OutlinedPencilSquare)
            ->color('warning')
            ->authorize('rectificar')
            ->modalDescription('El acta se reabre para que el docente a cargo corrija las notas y la vuelva a validar. El motivo queda en el registro de auditoría.')
            ->schema([
                Textarea::make('motivo')
                    ->label('Motivo de la rectificación')
                    ->required()
                    ->minLength(AutorizarRectificacion::MOTIVO_MINIMO)
                    ->maxLength(AutorizarRectificacion::MOTIVO_MAXIMO)
                    ->rows(3)
                    ->live(debounce: 400)
                    ->hint(fn (?string $state): string => mb_strlen((string) $state).' / '.AutorizarRectificacion::MOTIVO_MAXIMO),
            ])
            ->action(fn (Action $action, Acta $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (User $usuario) => app(AutorizarRectificacion::class)($record->id, (string) $data['motivo'], $usuario),
                'Rectificación autorizada',
            ))
            ->after(fn (Acta $record): Acta => $record->refresh());
    }
}
