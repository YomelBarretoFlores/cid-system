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
use App\Shared\Support\Filament\IdiomaConBandera;
use Filament\Actions\ActionGroup;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Enums\TextSize;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ViewColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

final class GruposTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['periodo', 'ciclo.idioma', 'programa', 'aula']))
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('codigo')->label('Código')->weight(FontWeight::SemiBold)->size(TextSize::Large)->searchable()->sortable(),
                        TextColumn::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->grow(false)
                            ->formatStateUsing(fn (EstadoGrupo $state): string => $state->etiqueta())
                            ->color(fn (EstadoGrupo $state): string => $state->color()),
                    ]),
                    TextColumn::make('ciclo.idioma.nombre')
                        ->label('Idioma y ciclo')
                        ->formatStateUsing(fn (Grupo $record): HtmlString => IdiomaConBandera::html($record->ciclo->idioma->nombre, GrupoForm::etiquetaDeCiclo($record->ciclo))),
                    TextColumn::make('programa.nombre')->label('Programa')->prefix('Programa ')->color('gray'),
                    TextColumn::make('periodo.nombre')->label('Periodo')->icon(Heroicon::OutlinedCalendar)->color('gray'),
                    Split::make([
                        TextColumn::make('modalidad')->label('Modalidad')->formatStateUsing(fn (Modalidad $state): string => $state->etiqueta())->color('gray'),
                        TextColumn::make('aula.codigo')->label('Aula')->placeholder('Virtual')->prefix('Aula ')->color('gray'),
                        TextColumn::make('es_acelerado')->label('Acelerado')->state(fn (Grupo $record): ?string => $record->es_acelerado ? 'Acelerado' : null)->badge()->color('info')->grow(false),
                    ]),
                    TextColumn::make('docente')->label('Docente')->icon(Heroicon::OutlinedUser)->state(fn (Grupo $record, ListGrupos $livewire): string => $livewire->docenteDelGrupo($record->id)),
                    ViewColumn::make('matriculados')->label('Matriculados')->view('filament.admin.columnas.ocupacion'),
                    TextColumn::make('vacantes_disponibles')->label('Vacantes')->formatStateUsing(fn (int $state): string => $state === 1 ? '1 vacante' : "{$state} vacantes")->color('gray'),
                ])->space(2),
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
