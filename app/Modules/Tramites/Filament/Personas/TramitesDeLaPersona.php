<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Personas;

use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Enums\PermisoTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\TramiteResource;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Support\Filament\TablaDeLaPersona;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TramitesDeLaPersona extends TablaDeLaPersona
{
    protected function permiso(): string
    {
        return PermisoTramite::Gestionar->value;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Tramite::query()->where('persona_id', $this->personaId))
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('codigo')->label('Código')->weight('medium'),
                TextColumn::make('tipo_nombre')->label('Trámite'),
                TextColumn::make('etapa_actual')->label('Etapa')->formatStateUsing(fn (Tramite $record): string => "{$record->nombreDeEtapa()} · {$record->avance()} %"),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn (EstadoTramite $state): string => $state->etiqueta())
                    ->color(fn (EstadoTramite $state): string => $state->color()),
                TextColumn::make('created_at')->label('Iniciado')->dateTime('d/m/Y H:i', 'America/Lima'),
            ])
            ->recordUrl(fn (Tramite $record): string => TramiteResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Sin trámites')
            ->emptyStateDescription('No ha iniciado ningún trámite.')
            ->paginated([10, 25]);
    }
}
