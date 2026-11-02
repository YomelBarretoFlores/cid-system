<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Documentos\Tables;

use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Models\Documento;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class DocumentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('codigo')->label('Código')->searchable()->weight('medium')->copyable(),
                TextColumn::make('tipo')->label('Tipo')->formatStateUsing(fn (TipoDocumento $state): string => $state->etiqueta()),
                TextColumn::make('estudiante_nombre')->label('Estudiante')->searchable(),
                TextColumn::make('curso')->label('Idioma y nivel')->state(fn (Documento $record): string => trim("{$record->idioma} ".($record->nivel?->etiqueta() ?? ''))),
                TextColumn::make('nota_final')->label('Nota final')->placeholder('—'),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->state(fn (Documento $record): string => $record->estaVigente() ? 'Vigente' : 'Anulado')
                    ->color(fn (string $state): string => $state === 'Vigente' ? 'success' : 'danger'),
                TextColumn::make('revision')
                    ->label('Revisión')
                    ->badge()
                    ->state(fn (Documento $record): ?string => $record->estaPorRevisar() ? 'Por revisar' : null)
                    ->color('warning')
                    ->placeholder('—'),
                TextColumn::make('emitido_en')->label('Emitido el')->dateTime('d/m/Y H:i')->sortable(),
            ])
            ->defaultSort('emitido_en', 'desc')
            ->filters([
                SelectFilter::make('tipo')->label('Tipo')->options(collect(TipoDocumento::cases())->mapWithKeys(fn (TipoDocumento $tipo): array => [$tipo->value => $tipo->etiqueta()])->all()),
                SelectFilter::make('estado')
                    ->label('Estado')
                    ->options(['vigente' => 'Vigente', 'anulado' => 'Anulado'])
                    ->query(fn (Builder $query, array $data): Builder => match ($data['value'] ?? null) {
                        'vigente' => $query->whereNull('anulado_en'),
                        'anulado' => $query->whereNotNull('anulado_en'),
                        default => $query,
                    }),
                Filter::make('por_revisar')
                    ->label('Por revisar tras una rectificación')
                    ->query(fn (Builder $query): Builder => $query->whereNotNull('revisar_desde')->whereNull('anulado_en')),
            ])
            ->recordActions([ViewAction::make()]);
    }
}
