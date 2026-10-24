<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Actions;

use App\Modules\Docencia\Actions\CambiarEstadoDocente;
use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Support\Filament\AccionDeNegocio;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Support\Icons\Heroicon;

final class CambiarEstado
{
    public static function make(): Action
    {
        return Action::make('cambiarEstado')
            ->label('Cambiar estado')
            ->icon(Heroicon::OutlinedArrowsRightLeft)
            ->authorize('cambiarEstado')
            ->fillForm(fn (Docente $record): array => ['estado' => $record->estado->value])
            ->schema([
                Select::make('estado')->label('Nuevo estado')->options(EstadoDocente::opciones())->required(),
            ])
            ->action(fn (Action $action, Docente $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (): Docente => app(CambiarEstadoDocente::class)($record->id, EstadoDocente::from(is_string($data['estado'] ?? null) ? $data['estado'] : '')),
                'Estado del docente actualizado',
            ))
            ->after(fn (Docente $record): Docente => $record->refresh());
    }
}
