<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\Tramites\Tables;

use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ListTramites;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class TramitesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->copyable(),
                TextColumn::make('persona_id')
                    ->label('Estudiante')
                    ->formatStateUsing(fn (int $state, ListTramites $livewire): string => $livewire->etiquetaDePersona($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereIn('persona_id', app(SubconsultaDePersonas::class)($search))),
                TextColumn::make('tipo_nombre')->label('Trámite'),
                TextColumn::make('etapa_actual')->label('Etapa')->formatStateUsing(fn (Tramite $record): string => "{$record->nombreDeEtapa()} · {$record->avance()} %"),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoTramite $state): string => $state->etiqueta())
                    ->color(fn (EstadoTramite $state): string => $state->color()),
                TextColumn::make('created_at')->label('Iniciado')->dateTime('d/m/Y H:i', 'America/Lima')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(array_column(array_map(fn (EstadoTramite $estado): array => [$estado->value, $estado->etiqueta()], EstadoTramite::cases()), 1, 0)),
                SelectFilter::make('tipo_tramite_id')
                    ->label('Trámite')
                    ->options(fn (): array => TipoTramite::query()->orderBy('nombre')->pluck('nombre', 'id')->all()),
            ])
            ->recordActions([
                ViewAction::make(),
            ]);
    }
}
