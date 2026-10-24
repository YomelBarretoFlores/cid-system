<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Aulas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class AulasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('capacidad')->label('Capacidad')->sortable(),
                TextColumn::make('ubicacion')->label('Ubicación')->placeholder('Sin ubicación'),
                IconColumn::make('activo')->label('Disponible')->boolean(),
            ])
            ->defaultSort('codigo')
            ->filters([
                TernaryFilter::make('activo')->label('Disponible'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
