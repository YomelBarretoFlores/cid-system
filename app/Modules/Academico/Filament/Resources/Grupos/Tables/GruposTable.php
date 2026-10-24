<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos\Tables;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Filament\Resources\Grupos\Actions\AccionesDeGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\ListGrupos;
use App\Modules\Academico\Filament\Resources\Grupos\Schemas\GrupoForm;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class GruposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['periodo', 'ciclo.idioma', 'aula']))
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->sortable(),
                TextColumn::make('periodo.nombre')->label('Periodo'),
                TextColumn::make('ciclo.idioma.nombre')
                    ->label('Idioma y ciclo')
                    ->formatStateUsing(fn (Grupo $record): string => GrupoForm::etiquetaDeCiclo($record->ciclo)),
                TextColumn::make('modalidad')->label('Modalidad')->formatStateUsing(fn (Modalidad $state): string => $state->etiqueta()),
                IconColumn::make('es_acelerado')->label('Acelerado')->boolean(),
                TextColumn::make('aula.codigo')->label('Aula')->placeholder('Virtual'),
                TextColumn::make('docente')->label('Docente')->state(fn (Grupo $record, ListGrupos $livewire): string => $livewire->docenteDelGrupo($record->id)),
                TextColumn::make('cupo_maximo')->label('Cupo'),
                TextColumn::make('matriculados')->label('Matriculados'),
                TextColumn::make('vacantes_disponibles')->label('Vacantes'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoGrupo $state): string => $state->etiqueta())
                    ->color(fn (EstadoGrupo $state): string => $state->color()),
            ])
            ->defaultSort('codigo')
            ->filters([
                SelectFilter::make('periodo')->label('Periodo')->relationship('periodo', 'nombre')->preload(),
                SelectFilter::make('estado')->label('Estado')->options(self::opciones(EstadoGrupo::cases())),
                SelectFilter::make('modalidad')->label('Modalidad')->options(self::opciones(Modalidad::cases())),
                SelectFilter::make('idioma')
                    ->label('Idioma')
                    ->options(fn (): array => Idioma::query()->orderBy('nombre')->pluck('nombre', 'id')->all())
                    ->query(fn (Builder $query, array $data): Builder => $query->when(
                        $data['value'] ?? null,
                        fn (Builder $porIdioma, mixed $idioma): Builder => $porIdioma->whereHas('ciclo', fn (Builder $ciclo): Builder => $ciclo->where('idioma_id', $idioma)),
                    )),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
                ActionGroup::make(AccionesDeGrupo::estados())->label('Estado'),
            ]);
    }

    /**
     * @param  list<EstadoGrupo|Modalidad>  $casos
     * @return array<string, string>
     */
    private static function opciones(array $casos): array
    {
        return collect($casos)->mapWithKeys(fn (EstadoGrupo|Modalidad $caso): array => [$caso->value => $caso->etiqueta()])->all();
    }
}
