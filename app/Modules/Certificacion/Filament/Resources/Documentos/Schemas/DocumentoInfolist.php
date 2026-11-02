<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Documentos\Schemas;

use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ViewEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class DocumentoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Documento')
                    ->columns(4)
                    ->schema([
                        TextEntry::make('codigo')->label('Código único')->copyable(),
                        TextEntry::make('tipo')->label('Tipo')->formatStateUsing(fn (TipoDocumento $state): string => $state->etiqueta()),
                        TextEntry::make('emitido_en')->label('Emitido el')->dateTime('d/m/Y H:i'),
                        TextEntry::make('emisor.name')->label('Emitido por'),
                        TextEntry::make('estudiante_nombre')->label('Estudiante')->columnSpan(2),
                        TextEntry::make('estudiante_documento')->label('Documento de identidad'),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->state(fn (Documento $record): string => $record->estaVigente() ? 'Vigente' : 'Anulado')
                            ->color(fn (string $state): string => $state === 'Vigente' ? 'success' : 'danger'),
                        TextEntry::make('idioma')->label('Idioma'),
                        TextEntry::make('nivel')->label('Nivel')->formatStateUsing(fn (Documento $record): string => $record->nivel?->etiqueta() ?? '—')->placeholder('—'),
                        TextEntry::make('horas_lectivas')->label('Total de horas lectivas')->placeholder('—'),
                        TextEntry::make('nota_final')->label('Nota final')->placeholder('—'),
                    ]),
                Section::make('Por revisar')
                    ->description('Un acta de la que se copiaron estas notas se rectificó después de emitirlo.')
                    ->columns(3)
                    ->visible(fn (Documento $record): bool => $record->estaPorRevisar())
                    ->schema([
                        TextEntry::make('motivo_revision')->label('Motivo')->columnSpan(2),
                        TextEntry::make('revisar_desde')->label('Desde')->dateTime('d/m/Y H:i'),
                    ]),
                Section::make('Anulación')
                    ->columns(3)
                    ->visible(fn (Documento $record): bool => ! $record->estaVigente())
                    ->schema([
                        TextEntry::make('motivo_anulacion')->label('Motivo'),
                        TextEntry::make('anulador.name')->label('Anulado por'),
                        TextEntry::make('anulado_en')->label('Anulado el')->dateTime('d/m/Y H:i'),
                    ]),
                Section::make('Detalle')
                    ->schema([ViewEntry::make('detalle')->hiddenLabel()->view('certificacion::filament.detalle-del-documento')]),
            ]);
    }
}
