<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Idiomas\Tables;

use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class IdiomasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('curriculo')->label('Currículo'),
                IconColumn::make('es_lengua_nativa')->label('Lengua originaria')->boolean(),
                TextColumn::make('ciclos_count')->label('Ciclos')->counts('ciclos'),
                IconColumn::make('activo')->label('Disponible')->boolean(),
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
