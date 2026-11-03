<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Schemas;

use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Filament\Resources\Matriculas\Schemas\SeccionDelPago;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ViewSolicitudClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class SolicitudClasificacionInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Solicitud')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('persona_id')
                            ->label('Estudiante')
                            ->formatStateUsing(fn (int $state): string => app(EtiquetasDePersonas::class)([$state])[$state] ?? '—')
                            ->columnSpan(2),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (EstadoSolicitudClasificacion $state): string => $state->etiqueta())
                            ->color(fn (EstadoSolicitudClasificacion $state): string => $state->color()),
                        TextEntry::make('idioma_id')
                            ->label('Idioma')
                            ->formatStateUsing(fn (int $state, ViewSolicitudClasificacion $livewire): string => $livewire->nombreDeIdioma($state)),
                        TextEntry::make('created_at')->label('Solicitado el')->dateTime('d/m/Y H:i'),
                        TextEntry::make('vence_en')->label('Vence el')->date('d/m/Y'),
                    ]),
                SeccionDelPago::make(),
                Section::make('Resultado')
                    ->columns(3)
                    ->visible(fn (SolicitudClasificacion $record): bool => $record->estado === EstadoSolicitudClasificacion::Evaluado)
                    ->schema([
                        TextEntry::make('fecha_examen')->label('Fecha del examen')->date('d/m/Y'),
                        TextEntry::make('puntaje')->label('Puntaje'),
                        TextEntry::make('ciclo_asignado_id')
                            ->label('Nivel asignado')
                            ->formatStateUsing(fn (int $state, SolicitudClasificacion $record, ViewSolicitudClasificacion $livewire): string => $livewire->idioma($record->idioma_id)->ciclos[$state] ?? '—'),
                        TextEntry::make('observaciones')->label('Observaciones')->placeholder('Sin observaciones.')->columnSpanFull(),
                        TextEntry::make('evaluador.name')->label('Evaluado por'),
                        TextEntry::make('evaluado_en')->label('Evaluado el')->dateTime('d/m/Y H:i'),
                        TextEntry::make('corrector.name')->label('Corregido por')->visible(fn (SolicitudClasificacion $record): bool => $record->corregido_en !== null),
                        TextEntry::make('corregido_en')->label('Corregido el')->dateTime('d/m/Y H:i')->visible(fn (SolicitudClasificacion $record): bool => $record->corregido_en !== null),
                        TextEntry::make('motivo_correccion')->label('Motivo de la corrección')->visible(fn (SolicitudClasificacion $record): bool => $record->corregido_en !== null),
                    ]),
            ]);
    }
}
