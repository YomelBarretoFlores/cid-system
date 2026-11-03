<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Habilitaciones\Tables;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Filament\Resources\Habilitaciones\Actions\AccionesDeHabilitacion;
use App\Modules\Matricula\Filament\Resources\Habilitaciones\Pages\ListHabilitaciones;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class HabilitacionesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('actualizador'))
            ->columns([
                TextColumn::make('persona_id')
                    ->label('Estudiante')
                    ->formatStateUsing(fn (int $state, ListHabilitaciones $livewire): string => $livewire->etiquetaDePersona($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereIn('persona_id', app(SubconsultaDePersonas::class)($search))),
                TextColumn::make('idioma_id')->label('Idioma')->formatStateUsing(fn (int $state, ListHabilitaciones $livewire): string => $livewire->nombreDeIdioma($state)),
                TextColumn::make('ciclo_id')->label('Ciclo habilitado')
                    ->formatStateUsing(fn (int $state, Habilitacion $record, ListHabilitaciones $livewire): string => $livewire->idioma($record->idioma_id)->ciclos[$state] ?? '—'),
                TextColumn::make('origen')->label('Origen')->formatStateUsing(fn (OrigenHabilitacion $state): string => $state->etiqueta()),
                TextColumn::make('motivo')->label('Motivo del ajuste')->placeholder('—')->wrap()->limit(80),
                TextColumn::make('actualizador.name')->label('Actualizado por')->placeholder('Sistema'),
                TextColumn::make('updated_at')->label('Actualizado el')->dateTime('d/m/Y H:i', 'America/Lima')->sortable(),
            ])
            ->defaultSort('updated_at', 'desc')
            ->filters([
                SelectFilter::make('idioma_id')->label('Idioma')->options(fn (): array => collect(app(CatalogoDeIdiomas::class)())->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id => $idioma->nombre])->all()),
                SelectFilter::make('origen')->label('Origen')->options(collect(OrigenHabilitacion::cases())->mapWithKeys(fn (OrigenHabilitacion $origen): array => [$origen->value => $origen->etiqueta()])->all()),
            ])
            ->recordActions([AccionesDeHabilitacion::ajustarFila()]);
    }
}
