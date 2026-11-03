<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Tables;

use App\Modules\Academico\Contracts\CatalogoDeIdiomas;
use App\Modules\Academico\Data\IdiomaDelCatalogo;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Actions\AccionesDeSolicitudClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ListSolicitudesClasificacion;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use App\Shared\Support\Filament\IdiomaConBandera;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

final class SolicitudesClasificacionTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('persona_id')
                    ->label('Estudiante')
                    ->formatStateUsing(fn (int $state, ListSolicitudesClasificacion $livewire): string => $livewire->etiquetaDePersona($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereIn('persona_id', app(SubconsultaDePersonas::class)($search))),
                TextColumn::make('idioma_id')
                    ->label('Idioma')
                    ->formatStateUsing(fn (int $state, ListSolicitudesClasificacion $livewire): HtmlString => IdiomaConBandera::html($livewire->nombreDeIdioma($state))),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoSolicitudClasificacion $state): string => $state->etiqueta())
                    ->color(fn (EstadoSolicitudClasificacion $state): string => $state->color()),
                TextColumn::make('created_at')->label('Solicitado el')->dateTime('d/m/Y H:i')->sortable(),
                TextColumn::make('vence_en')->label('Vence el')->date('d/m/Y'),
                TextColumn::make('puntaje')->label('Puntaje')->placeholder('—'),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(collect(EstadoSolicitudClasificacion::cases())->mapWithKeys(fn (EstadoSolicitudClasificacion $estado): array => [$estado->value => $estado->etiqueta()])->all()),
                SelectFilter::make('idioma_id')->label('Idioma')->options(fn (): array => collect(app(CatalogoDeIdiomas::class)())->mapWithKeys(fn (IdiomaDelCatalogo $idioma): array => [$idioma->id => $idioma->nombre])->all()),
            ])
            ->recordActions([
                ViewAction::make(),
                AccionesDeSolicitudClasificacion::registrarResultado(),
            ]);
    }
}
