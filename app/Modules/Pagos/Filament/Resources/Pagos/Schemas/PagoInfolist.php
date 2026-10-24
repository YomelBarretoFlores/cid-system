<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Schemas;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Filament\Resources\Pagos\Tables\PagosTable;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Contracts\EtiquetasDePersonas;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

final class PagoInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Pago')
                    ->columns(3)
                    ->schema([
                        TextEntry::make('persona_id')
                            ->label('Persona')
                            ->formatStateUsing(fn (int $state): string => app(EtiquetasDePersonas::class)([$state])[$state] ?? '—')
                            ->columnSpan(2),
                        TextEntry::make('estado')
                            ->label('Estado')
                            ->badge()
                            ->formatStateUsing(fn (EstadoPago $state): string => $state->etiqueta())
                            ->color(fn (EstadoPago $state): string => PagosTable::color($state)),
                        TextEntry::make('concepto.nombre')->label('Concepto TUPA/TUSNE'),
                        TextEntry::make('monto')->label('Monto')->money('PEN'),
                        TextEntry::make('tarifa.monto')->label('Tarifa vigente')->money('PEN')->placeholder('Sin tarifa'),
                        TextEntry::make('medio_pago')->label('Medio de pago')->formatStateUsing(fn (MedioPago $state): string => $state->etiqueta()),
                        TextEntry::make('numero_operacion')->label('N.° de recibo u operación')->copyable(),
                        TextEntry::make('fecha_pago')->label('Fecha de pago')->date('d/m/Y'),
                        TextEntry::make('registrador.name')->label('Registrado por')->placeholder('La propia persona'),
                        TextEntry::make('created_at')->label('Registrado el')->dateTime('d/m/Y H:i', 'America/Lima'),
                    ]),
                Section::make('Revisión')
                    ->columns(3)
                    ->visible(fn (Pago $record): bool => $record->validado_por !== null)
                    ->schema([
                        TextEntry::make('validador.name')->label('Revisado por'),
                        TextEntry::make('validado_en')->label('Revisado el')->dateTime('d/m/Y H:i', 'America/Lima'),
                        TextEntry::make('motivo_rechazo')->label('Motivo del rechazo')->visible(fn (Pago $record): bool => $record->motivo_rechazo !== null),
                    ]),
                Section::make('Anulación')
                    ->columns(3)
                    ->visible(fn (Pago $record): bool => $record->estado === EstadoPago::Anulado)
                    ->schema([
                        TextEntry::make('anulador.name')->label('Anulado por'),
                        TextEntry::make('anulado_en')->label('Anulado el')->dateTime('d/m/Y H:i', 'America/Lima'),
                        TextEntry::make('motivo_anulacion')->label('Motivo'),
                    ]),
            ]);
    }
}
