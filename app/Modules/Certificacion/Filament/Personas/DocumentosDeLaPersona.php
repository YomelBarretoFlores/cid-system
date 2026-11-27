<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Personas;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Filament\Resources\Documentos\DocumentoResource;
use App\Modules\Certificacion\Models\Documento;
use App\Shared\Support\Filament\TablaDeLaPersona;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class DocumentosDeLaPersona extends TablaDeLaPersona
{
    protected function permiso(): string
    {
        return PermisoCertificacion::Documentos->value;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Documento::query()->where('persona_id', $this->personaId))
            ->defaultSort('emitido_en', 'desc')
            ->columns([
                TextColumn::make('codigo')->label('Código')->weight('medium'),
                TextColumn::make('tipo')->label('Tipo')->formatStateUsing(fn (TipoDocumento $state): string => $state->etiqueta()),
                TextColumn::make('curso')->label('Idioma y nivel')->state(fn (Documento $record): string => trim("{$record->idioma} ".($record->nivel?->etiqueta() ?? ''))),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->state(fn (Documento $record): string => $record->estaVigente() ? 'Vigente' : 'Anulado')
                    ->color(fn (string $state): string => $state === 'Vigente' ? 'success' : 'danger'),
                TextColumn::make('emitido_en')->label('Emitido el')->dateTime('d/m/Y H:i', 'America/Lima'),
            ])
            ->recordUrl(fn (Documento $record): string => DocumentoResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Sin documentos')
            ->emptyStateDescription('No se le ha emitido ninguna constancia ni certificado.')
            ->paginated([10, 25]);
    }
}
