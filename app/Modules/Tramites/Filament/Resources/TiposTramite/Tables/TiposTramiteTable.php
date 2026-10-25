<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\TiposTramite\Tables;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tramites\Enums\AccionFinal;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

final class TiposTramiteTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('tipo_concepto')->label('Tasa')->placeholder('Sin tasa')->formatStateUsing(fn (?TipoConcepto $state): string => $state?->etiqueta() ?? 'Sin tasa'),
                TextColumn::make('accion_final')->label('Al finalizar')->formatStateUsing(fn (AccionFinal $state): string => $state->etiqueta()),
                TextColumn::make('etapas_count')->label('Etapas')->counts('etapas'),
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
