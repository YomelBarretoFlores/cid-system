<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes\Schemas;

use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ViewDocente;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class DocenteInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos personales')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('codigo')->label('ID docente')->copyable(),
                        TextEntry::make('nombres')->label('Nombres completos')->state(fn (ViewDocente $livewire): string => $livewire->persona()->nombreCompleto)->columnSpan(2),
                        TextEntry::make('documento')->label('DNI/CE')->state(fn (ViewDocente $livewire): string => $livewire->persona()->documento),
                        TextEntry::make('correo_institucional')->label('Correo institucional'),
                        TextEntry::make('correo_personal')->label('Correo personal')->state(fn (ViewDocente $livewire): ?string => $livewire->persona()->correo)->placeholder('—'),
                        TextEntry::make('celular')->label('Celular')->state(fn (ViewDocente $livewire): ?string => $livewire->persona()->celular)->placeholder('—'),
                        TextEntry::make('estado')->label('Estado')->badge()
                            ->formatStateUsing(fn (EstadoDocente $state): string => $state->etiqueta())
                            ->color(fn (EstadoDocente $state): string => $state->color()),
                    ]),
                Section::make('Datos para el expediente')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('ruc')->label('RUC')->copyable(),
                        TextEntry::make('banco')->label('Banco'),
                        TextEntry::make('cuenta_enmascarada')->label('Número de cuenta'),
                        TextEntry::make('condicion_laboral')->label('Condición laboral')->formatStateUsing(fn (CondicionLaboral $state): string => $state->etiqueta()),
                    ]),
                Section::make('Perfil académico')
                    ->schema([
                        TextEntry::make('idiomas_habilitados')
                            ->label('Idiomas habilitados y nivel máximo autorizado')
                            ->state(fn (ViewDocente $livewire): array => $livewire->idiomasHabilitados())
                            ->listWithLineBreaks()
                            ->placeholder('—'),
                    ]),
            ]);
    }
}
