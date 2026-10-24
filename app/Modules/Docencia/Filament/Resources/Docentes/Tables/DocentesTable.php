<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Tables;

use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ListDocentes;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class DocentesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->columns([
                TextColumn::make('codigo')->label('ID docente')->searchable(),
                TextColumn::make('persona_id')
                    ->label('Docente')
                    ->formatStateUsing(fn (int $state, ListDocentes $livewire): string => $livewire->etiquetaDePersona($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereIn('persona_id', app(SubconsultaDePersonas::class)($search))),
                TextColumn::make('correo_institucional')->label('Correo institucional')->toggleable(),
                TextColumn::make('condicion_laboral')->label('Condición')->formatStateUsing(fn (CondicionLaboral $state): string => $state->etiqueta()),
                TextColumn::make('cuenta')->label('Cuenta')->state(fn (Docente $record): string => $record->cuenta_enmascarada)->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn (EstadoDocente $state): string => $state->etiqueta())
                    ->color(fn (EstadoDocente $state): string => $state->color()),
            ])
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(EstadoDocente::opciones()),
                SelectFilter::make('condicion_laboral')->label('Condición laboral')->options(CondicionLaboral::opciones()),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
