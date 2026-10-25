<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Actas\Schemas;

use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Models\Acta;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class ActaInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Datos del curso y del docente')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('idioma')->label('Idioma'),
                        TextEntry::make('nivel')->label('Nivel y ciclo')->state(fn (Acta $record): string => "{$record->nivel->etiqueta()} · {$record->ciclo_nombre}"),
                        TextEntry::make('periodo')->label('Periodo académico'),
                        TextEntry::make('grupo_codigo')->label('Grupo'),
                        TextEntry::make('docente_id')->label('ID del docente'),
                        TextEntry::make('docente_nombre')->label('Docente')->columnSpan(2),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (EstadoActa $state): string => $state->etiqueta())
                            ->color(fn (EstadoActa $state): string => $state === EstadoActa::Cerrada ? 'success' : 'warning'),
                        TextEntry::make('esquema_nombre')->label('Sistema de evaluación'),
                        TextEntry::make('validador.name')->label('Validada por'),
                        TextEntry::make('validada_en')->label('Validada el')->dateTime('d/m/Y H:i'),
                    ]),
                Section::make('Calificaciones')
                    ->schema([ViewEntry::make('notas')->hiddenLabel()->view('certificacion::filament.calificaciones-del-acta')]),
                Section::make('Rectificaciones')
                    ->schema([
                        RepeatableEntry::make('rectificaciones')
                            ->hiddenLabel()
                            ->placeholder('Sin rectificaciones.')
                            ->columns(4)
                            ->schema([
                                TextEntry::make('motivo')->label('Motivo')->columnSpan(2),
                                TextEntry::make('autorizador.name')->label('Autorizada por'),
                                TextEntry::make('autorizada_en')->label('Autorizada el')->dateTime('d/m/Y H:i'),
                                TextEntry::make('cerrada_en')->label('Acta cerrada de nuevo el')->dateTime('d/m/Y H:i')->placeholder('Pendiente de validación'),
                            ]),
                    ]),
            ]);
    }
}
