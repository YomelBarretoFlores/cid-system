<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Programas\Tables;

use App\Modules\Academico\Enums\RegimenDePrograma;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ProgramasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('regimen')->label('Régimen de ciclo')->formatStateUsing(fn (RegimenDePrograma $state): string => $state->etiqueta()),
                IconColumn::make('activo')->label('Disponible')->boolean(),
                IconColumn::make('por_defecto')->label('Por defecto')->boolean(),
            ])
            ->defaultSort('nombre')
            ->filters([
                TernaryFilter::make('activo')->label('Disponible'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
