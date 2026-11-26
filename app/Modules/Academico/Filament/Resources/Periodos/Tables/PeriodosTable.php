<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Periodos\Tables;

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Filament\Resources\Periodos\Actions\AccionesDePeriodo;
use App\Modules\Academico\Models\Periodo;
use Filament\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PeriodosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('anio')->label('Año')->sortable(),
                TextColumn::make('numero')->label('Número')->sortable(),
                TextColumn::make('nombre')->label('Nombre')->searchable(),
                TextColumn::make('fecha_inicio')->label('Inicio')->date('d/m/Y')->sortable(),
                TextColumn::make('fecha_fin')->label('Fin')->date('d/m/Y'),
                TextColumn::make('matricula_inicio')->label('Matrícula desde')->date('d/m/Y'),
                TextColumn::make('matricula_fin')->label('Matrícula hasta')->date('d/m/Y'),
                TextColumn::make('matricula_extemporanea_fin')->label('Matrícula extemporánea hasta')->date('d/m/Y')->placeholder('No tiene'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoPeriodo $state): string => $state->etiqueta())
                    ->color(fn (EstadoPeriodo $state): string => $state->color()),
            ])
            ->defaultSort(fn (Builder $query): Builder => $query->orderByDesc('anio')->orderByDesc('numero'))
            ->filters([
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(collect(EstadoPeriodo::cases())->mapWithKeys(fn (EstadoPeriodo $estado): array => [$estado->value => $estado->etiqueta()])->all()),
                SelectFilter::make('anio')->label('Año')->options(fn (): array => self::anios()),
            ])
            ->recordActions([
                EditAction::make(),
                ...AccionesDePeriodo::cambiosDeEstado(),
            ]);
    }

    /**
     * @return array<int, int>
     */
    private static function anios(): array
    {
        return Periodo::query()->distinct()->orderByDesc('anio')->pluck('anio', 'anio')->all();
    }
}
