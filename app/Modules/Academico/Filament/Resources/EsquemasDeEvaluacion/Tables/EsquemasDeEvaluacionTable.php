<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Tables;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class EsquemasDeEvaluacionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('idioma.nombre')->label('Idioma')->placeholder('Todos'),
                TextColumn::make('nivel')->label('Nivel')->formatStateUsing(fn (?NivelCurricular $state): string => $state?->etiqueta() ?? 'Todos')->placeholder('Todos'),
                TextColumn::make('componentes')
                    ->label('Componentes')
                    ->state(fn (EsquemaEvaluacion $record): string => $record->componentes()->get()
                        ->groupBy(fn (ComponenteEvaluacion $componente): string => $componente->grupo->etiqueta())
                        ->map(fn ($componentes, string $grupo): string => "{$grupo}: ".$componentes->pluck('nombre')->implode(', '))
                        ->implode(' · ')),
                TextColumn::make('nota_minima_aprobatoria')->label('Aprueba con'),
                TextColumn::make('vigente_desde')->label('Desde')->date('d/m/Y')->sortable(),
                TextColumn::make('vigente_hasta')->label('Hasta')->date('d/m/Y')->placeholder('Sin fecha de fin'),
            ])
            ->defaultSort('vigente_desde', 'desc')
            ->recordActions([EditAction::make()]);
    }
}
