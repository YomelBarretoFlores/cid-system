<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\EsquemasDeEvaluacion\Tables;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\RegimenDeEsquema;
use App\Modules\Academico\Enums\TipoDeComponente;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class EsquemasDeEvaluacionTable
{
    /**
     * @param  array<int, ComponenteEvaluacion>  $columnas
     */
    private static function resumen(array $columnas): string
    {
        $ingresadas = count(array_filter($columnas, fn (ComponenteEvaluacion $columna): bool => $columna->tipo === TipoDeComponente::Ingresada));
        $ciclos = array_map(fn (ComponenteEvaluacion $columna): string => $columna->nombre, array_filter($columnas, fn (ComponenteEvaluacion $columna): bool => $columna->ciclo !== null));

        return "{$ingresadas} del docente, ".(count($columnas) - $ingresadas).' calculadas · nota del ciclo: '.implode(' y ', $ciclos);
    }

    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nombre')->label('Nombre')->searchable()->sortable(),
                TextColumn::make('idioma.nombre')->label('Idioma')->placeholder('Todos'),
                TextColumn::make('nivel')->label('Nivel')->formatStateUsing(fn (?NivelCurricular $state): string => $state?->etiqueta() ?? 'Todos')->placeholder('Todos'),
                TextColumn::make('programa.nombre')->label('Programa')->placeholder('Todos'),
                TextColumn::make('regimen')->label('Régimen')->formatStateUsing(fn (?RegimenDeEsquema $state): string => $state?->etiqueta() ?? 'Ambos')->placeholder('Ambos'),
                TextColumn::make('componentes')
                    ->label('Columnas')
                    ->state(fn (EsquemaEvaluacion $record): string => self::resumen($record->componentes()->get()->all()))
                    ->wrap(),
                TextColumn::make('nota_minima_aprobatoria')->label('Aprueba con'),
                TextColumn::make('vigente_desde')->label('Desde')->date('d/m/Y')->sortable(),
                TextColumn::make('vigente_hasta')->label('Hasta')->date('d/m/Y')->placeholder('Sin fecha de fin'),
            ])
            ->defaultSort('vigente_desde', 'desc')
            ->recordActions([EditAction::make()]);
    }
}
