<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas\Tables;

use App\Modules\Personas\Filament\Resources\Personas\Schemas\PersonaForm;
use App\Modules\Personas\Models\Persona;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PersonasTable
{
    public static function configure(Table $table): Table
    {
        $vinculos = PersonaForm::vinculos(todos: true);
        $documentos = PersonaForm::documentos(todos: true);

        return $table
            ->columns([
                TextColumn::make('numero_documento')
                    ->label('Documento')
                    ->formatStateUsing(fn (string $state, Persona $record): string => ($documentos[$record->tipo_documento] ?? $record->tipo_documento)." {$state}")
                    ->searchable(query: self::buscar(...)),
                TextColumn::make('nombre_completo')
                    ->label('Apellidos y nombres')
                    ->sortable(['apellido_paterno', 'apellido_materno', 'nombres']),
                TextColumn::make('tipo_vinculo')
                    ->label('Vínculo')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => $vinculos[$state] ?? $state),
                TextColumn::make('correo')->label('Correo')->placeholder('—'),
            ])
            ->defaultSort('nombre_completo')
            ->filters([
                SelectFilter::make('tipo_vinculo')->label('Vínculo')->options($vinculos),
                SelectFilter::make('tipo_documento')->label('Tipo de documento')->options($documentos),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }

    /**
     * @param  Builder<Persona>  $query
     * @return Builder<Persona>
     */
    private static function buscar(Builder $query, string $search): Builder
    {
        return $query->coincideCon($search);
    }
}
