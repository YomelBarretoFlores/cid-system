<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Tables;

use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ListDocentes;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

final class DocentesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('id', 'desc')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with('idiomas'))
            ->contentGrid(['md' => 2, 'xl' => 3])
            ->columns([
                Stack::make([
                    Split::make([
                        TextColumn::make('persona_id')
                            ->label('Docente')
                            ->weight(FontWeight::SemiBold)
                            ->formatStateUsing(fn (int $state, ListDocentes $livewire): string => $livewire->etiquetaDePersona($state))
                            ->searchable(query: fn (Builder $query, string $search): Builder => $query->whereIn('persona_id', app(SubconsultaDePersonas::class)($search))),
                        TextColumn::make('estado')->label('Estado')->badge()->grow(false)
                            ->formatStateUsing(fn (EstadoDocente $state): string => $state->etiqueta())
                            ->color(fn (EstadoDocente $state): string => $state->color()),
                    ]),
                    TextColumn::make('codigo')->label('ID docente')->color('gray')->searchable(),
                    TextColumn::make('idiomas')->label('Idiomas')->state(fn (Docente $record, ListDocentes $livewire): HtmlString|string => $livewire->idiomasDelDocente($record)),
                    TextColumn::make('condicionLaboral.nombre')->label('Condición')->color('gray'),
                    TextColumn::make('gradoAcademico.nombre')->label('Grado')->placeholder('Sin registrar')->color('gray'),
                    TextColumn::make('correo_institucional')->label('Correo institucional')->icon(Heroicon::OutlinedEnvelope)->color('gray'),
                ])->space(2),
            ])
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(EstadoDocente::opciones()),
                SelectFilter::make('condicion_laboral_id')->label('Condición laboral')->relationship('condicionLaboral', 'nombre'),
                SelectFilter::make('grado_academico_id')->label('Grado académico')->relationship('gradoAcademico', 'nombre'),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make(),
            ]);
    }
}
