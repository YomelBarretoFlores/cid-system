<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Tables;

use App\Modules\Academico\Contracts\CatalogoDeGrupos;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\Actions\AccionesDeMatricula;
use App\Modules\Matricula\Filament\Resources\Matriculas\Pages\ListMatriculas;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class MatriculasTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('persona_id')
                    ->label('Estudiante')
                    ->formatStateUsing(fn (int $state, ListMatriculas $livewire): string => $livewire->etiquetaDePersona($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereIn('persona_id', app(SubconsultaDePersonas::class)($search))),
                TextColumn::make('grupo_id')
                    ->label('Grupo')
                    ->formatStateUsing(fn (int $state, ListMatriculas $livewire): string => $livewire->etiquetaDeGrupo($state)),
                TextColumn::make('condicion')->label('Condición')->formatStateUsing(fn (CondicionMatricula $state): string => $state->etiqueta()),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoMatricula $state): string => $state->etiqueta())
                    ->color(fn (EstadoMatricula $state): string => $state === EstadoMatricula::Activa ? 'success' : 'gray'),
                TextColumn::make('matriculado_en')->label('Matriculado el')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('matriculado_en', 'desc')
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(collect(EstadoMatricula::cases())->mapWithKeys(fn (EstadoMatricula $estado): array => [$estado->value => $estado->etiqueta()])->all()),
                SelectFilter::make('grupo_id')->label('Grupo')->options(fn (): array => app(CatalogoDeGrupos::class)())->searchable(),
            ])
            ->recordActions([
                ViewAction::make(),
                ...AccionesDeMatricula::movimientos(),
            ]);
    }
}
