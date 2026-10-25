<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Actas\Tables;

use App\Modules\Academico\Contracts\CatalogoDePeriodos;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Models\Acta;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class ActasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->withCount(['notas', 'notas as aprobados_count' => fn (Builder $notas): Builder => $notas->where('estado_final', EstadoFinal::Aprobado)]))
            ->columns([
                TextColumn::make('grupo_codigo')->label('Grupo')->searchable()->weight('medium'),
                TextColumn::make('curso')->label('Idioma y ciclo')->state(fn (Acta $record): string => "{$record->idioma} · {$record->ciclo_nombre}"),
                TextColumn::make('periodo')->label('Periodo'),
                TextColumn::make('docente_nombre')->label('Docente')->searchable(),
                TextColumn::make('aprobados')->label('Aprobados')->state(fn (Acta $record): string => "{$record->getAttribute('aprobados_count')} de {$record->getAttribute('notas_count')}"),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoActa $state): string => $state->etiqueta())
                    ->color(fn (EstadoActa $state): string => $state === EstadoActa::Cerrada ? 'success' : 'warning'),
                TextColumn::make('validada_en')->label('Validada el')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('validada_en', 'desc')
            ->filters([
                SelectFilter::make('periodo_id')->label('Periodo')->options(fn (): array => app(CatalogoDePeriodos::class)()),
                SelectFilter::make('estado')->label('Estado')->options(collect(EstadoActa::cases())->mapWithKeys(fn (EstadoActa $estado): array => [$estado->value => $estado->etiqueta()])->all()),
            ])
            ->recordActions([ViewAction::make()]);
    }
}
