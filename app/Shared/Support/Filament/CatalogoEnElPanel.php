<?php

declare(strict_types=1);

namespace App\Shared\Support\Filament;

use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

// Form and table shared by the small catalogs: name, order and availability; the code is shown, never edited.
final class CatalogoEnElPanel
{
    public static function formulario(Schema $schema, string $repetido): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                TextInput::make('nombre')
                    ->label('Nombre')
                    ->required()
                    ->maxLength(60)
                    ->unique(ignoreRecord: true)
                    ->validationMessages(['unique' => $repetido]),
                TextInput::make('orden')->label('Orden en las listas')->integer()->minValue(0)->maxValue(999)->default(0)->required(),
                TextEntry::make('codigo')->label('Código')->visibleOn('edit')->helperText('Se genera al crear y no cambia.'),
                Toggle::make('activo')->label('Disponible en los formularios')->default(true),
            ]);
    }

    public static function tabla(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('codigo')->label('Código')->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('orden')->label('Orden')->sortable(),
                IconColumn::make('activo')->label('Disponible')->boolean(),
            ])
            ->defaultSort('orden')
            ->filters([TernaryFilter::make('activo')->label('Disponible')])
            ->recordActions([EditAction::make()]);
    }
}
