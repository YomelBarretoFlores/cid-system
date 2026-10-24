<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos\Tables;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Filament\Resources\Pagos\Actions\AccionesDePago;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ListPagos;
use App\Modules\Personas\Contracts\BusquedaDePersonas;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\DatePicker;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

final class PagosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero_operacion')->label('N.° de operación')->searchable()->copyable(),
                TextColumn::make('persona_id')
                    ->label('Persona')
                    ->formatStateUsing(fn (int $state, ListPagos $livewire): string => $livewire->etiquetaDePersona($state))
                    ->searchable(query: fn (Builder $query, string $search): Builder => $query
                        ->whereIn('persona_id', array_keys(app(BusquedaDePersonas::class)($search, 200)))),
                TextColumn::make('concepto.nombre')->label('Concepto'),
                TextColumn::make('monto')->label('Monto')->money('PEN'),
                TextColumn::make('medio_pago')->label('Medio')->formatStateUsing(fn (MedioPago $state): string => $state->etiqueta()),
                TextColumn::make('fecha_pago')->label('Fecha de pago')->date('d/m/Y')->sortable(),
                TextColumn::make('estado')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (EstadoPago $state): string => $state->etiqueta())
                    ->color(fn (EstadoPago $state): string => self::color($state)),
                TextColumn::make('validado_en')
                    ->label('Revisado')
                    ->dateTime('d/m/Y H:i', 'America/Lima')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('fecha_pago', 'desc')
            ->filters([
                SelectFilter::make('estado')->label('Estado')->options(self::opciones(EstadoPago::cases())),
                SelectFilter::make('concepto')->label('Concepto')->relationship('concepto', 'nombre')->preload(),
                SelectFilter::make('medio_pago')->label('Medio')->options(self::opciones(MedioPago::cases())),
                Filter::make('fecha_pago')
                    ->schema([
                        DatePicker::make('desde')->label('Pagado desde'),
                        DatePicker::make('hasta')->label('Pagado hasta'),
                    ])
                    ->query(fn (Builder $query, array $data): Builder => $query
                        ->when($data['desde'] ?? null, fn (Builder $q, string $desde): Builder => $q->whereDate('fecha_pago', '>=', $desde))
                        ->when($data['hasta'] ?? null, fn (Builder $q, string $hasta): Builder => $q->whereDate('fecha_pago', '<=', $hasta))),
            ])
            ->recordActions([
                ViewAction::make(),
                ...AccionesDePago::revision(),
            ]);
    }

    public static function color(EstadoPago $estado): string
    {
        return match ($estado) {
            EstadoPago::Pendiente => 'warning',
            EstadoPago::Validado => 'success',
            EstadoPago::Rechazado => 'danger',
            EstadoPago::Anulado => 'gray',
        };
    }

    /**
     * @param  list<EstadoPago|MedioPago>  $casos
     * @return array<string, string>
     */
    private static function opciones(array $casos): array
    {
        return collect($casos)->mapWithKeys(fn (EstadoPago|MedioPago $caso): array => [$caso->value => $caso->etiqueta()])->all();
    }
}
