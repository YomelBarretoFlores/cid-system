<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Personas;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Filament\Resources\Pagos\PagoResource;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Support\Filament\TablaDeLaPersona;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class PagosDeLaPersona extends TablaDeLaPersona
{
    protected function permiso(): string
    {
        return PermisoPago::Ver->value;
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(Pago::query()->with(['concepto', 'medioDePago'])->where('persona_id', $this->personaId))
            ->defaultSort('fecha_pago', 'desc')
            ->columns([
                TextColumn::make('concepto.nombre')->label('Concepto'),
                TextColumn::make('numero_operacion')->label('N.° de operación'),
                TextColumn::make('medioDePago.nombre')->label('Medio'),
                TextColumn::make('monto')->label('Monto')->money('PEN')->alignEnd(),
                TextColumn::make('fecha_pago')->label('Fecha de pago')->date('d/m/Y'),
                TextColumn::make('estado')->label('Estado')->badge()
                    ->formatStateUsing(fn (EstadoPago $state): string => $state->etiqueta())
                    ->color(fn (EstadoPago $state): string => $state->color()),
            ])
            ->recordUrl(fn (Pago $record): string => PagoResource::getUrl('view', ['record' => $record]))
            ->emptyStateHeading('Sin pagos')
            ->emptyStateDescription('No tiene pagos registrados.')
            ->paginated([10, 25]);
    }
}
