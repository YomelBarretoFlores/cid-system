<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\Tables;

use App\Modules\Pagos\Enums\TipoConcepto;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class ConceptosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('tipo')->label('Servicio')->formatStateUsing(fn (TipoConcepto $state): string => $state->etiqueta()),
                TextColumn::make('fuenteDeConcepto.nombre')->label('Fuente')->badge(),
                IconColumn::make('activo')->label('Disponible')->boolean(),
            ])
            ->defaultSort('codigo')
            ->filters([
                TernaryFilter::make('activo')->label('Disponible'),
                SelectFilter::make('fuente_id')
                    ->label('Fuente')
                    ->relationship('fuenteDeConcepto', 'nombre'),
            ])
            ->recordActions([
                EditAction::make(),
            ]);
    }
}
