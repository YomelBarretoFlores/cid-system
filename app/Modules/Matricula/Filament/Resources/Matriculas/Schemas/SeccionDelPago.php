<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Matriculas\Schemas;

use App\Modules\Pagos\Contracts\ConsultaDePago;
use App\Modules\Pagos\Data\DetalleDePago;
use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Illuminate\Database\Eloquent\Model;

// The payment behind an enrollment or a placement request, for staff who may see payments (TdR M1.3: "Validación de Pago").
final class SeccionDelPago
{
    public static function make(): Section
    {
        return Section::make('Pago')
            ->columns(4)
            ->visible(fn (Model $record): bool => $record->getAttribute('pago_id') !== null && (auth()->user()?->can(PermisoPago::Ver->value) ?? false))
            ->schema([
                TextEntry::make('pago_concepto')->label('Concepto')->state(fn (Model $record): string => self::pago($record)->concepto),
                TextEntry::make('pago_monto')->label('Monto')->state(fn (Model $record): string => 'S/ '.self::pago($record)->monto),
                TextEntry::make('pago_operacion')->label('N.° de operación')->state(fn (Model $record): string => self::pago($record)->numeroOperacion),
                TextEntry::make('pago_estado')->label('Estado del pago')->badge()
                    ->state(fn (Model $record): EstadoPago => self::pago($record)->estado)
                    ->formatStateUsing(fn (EstadoPago $state): string => $state->etiqueta())
                    ->color(fn (EstadoPago $state): string => $state->color())
                    ->url(fn (Model $record): ?string => self::enlace(self::pago($record))),
            ]);
    }

    private static function pago(Model $record): DetalleDePago
    {
        return app(ConsultaDePago::class)((int) $record->getAttribute('pago_id'));
    }

    private static function enlace(DetalleDePago $pago): ?string
    {
        return $pago->ulid === null ? null : route('filament.admin.resources.pagos.view', $pago->ulid);
    }
}
