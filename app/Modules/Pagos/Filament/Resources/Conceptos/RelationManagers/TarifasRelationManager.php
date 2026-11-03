<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\RelationManagers;

use App\Modules\Pagos\Actions\CerrarTarifa;
use App\Modules\Pagos\Actions\EliminarTarifa;
use App\Modules\Pagos\Actions\RegistrarTarifa;
use App\Modules\Pagos\Filament\Schemas\CampoDeMonto;
use App\Modules\Pagos\Models\Tarifa;
use App\Modules\Personas\Enums\TipoVinculo;
use App\Shared\Support\Filament\AccionDeNegocio;
use Carbon\CarbonImmutable;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

final class TarifasRelationManager extends RelationManager
{
    protected static string $relationship = 'tarifas';

    protected static ?string $title = 'Tarifas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('monto')
            ->columns([
                TextColumn::make('tipo_vinculo')->label('Vínculo')->formatStateUsing(fn (TipoVinculo $state): string => $state->etiqueta()),
                TextColumn::make('monto')->label('Monto')->money('PEN'),
                TextColumn::make('vigente_desde')->label('Desde')->date('d/m/Y')->sortable(),
                TextColumn::make('vigente_hasta')->label('Hasta')->date('d/m/Y')->placeholder('Sin fecha de cierre'),
            ])
            ->defaultSort('vigente_desde', 'desc')
            ->headerActions([
                $this->registrar(),
            ])
            ->recordActions([
                $this->cerrar(),
                DeleteAction::make()
                    ->action(fn (Action $action, Tarifa $record) => AccionDeNegocio::ejecutar(
                        $action,
                        fn () => app(EliminarTarifa::class)($record->id),
                        'Tarifa eliminada',
                    )),
            ]);
    }

    private function registrar(): Action
    {
        return Action::make('registrarTarifa')
            ->label('Nueva tarifa')
            ->icon(Heroicon::OutlinedPlus)
            ->authorize('create')
            ->modalDescription('Si hay una tarifa abierta para el mismo vínculo, se cierra el día anterior al inicio de la nueva.')
            ->schema([
                Select::make('tipo_vinculo')
                    ->label('Vínculo con la UNASAM')
                    ->options(collect(TipoVinculo::cases())->mapWithKeys(fn (TipoVinculo $vinculo): array => [$vinculo->value => $vinculo->etiqueta()])->all())
                    ->required(),
                CampoDeMonto::make(),
                DatePicker::make('vigente_desde')->label('Vigente desde')->required(),
                DatePicker::make('vigente_hasta')->label('Vigente hasta')->afterOrEqual('vigente_desde'),
            ])
            ->action(fn (Action $action, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (): Tarifa => app(RegistrarTarifa::class)(
                    $this->getOwnerRecord()->getKey(),
                    TipoVinculo::from($data['tipo_vinculo']),
                    (string) $data['monto'],
                    CarbonImmutable::parse($data['vigente_desde']),
                    filled($data['vigente_hasta'] ?? null) ? CarbonImmutable::parse($data['vigente_hasta']) : null,
                ),
                'Tarifa registrada',
            ));
    }

    private function cerrar(): Action
    {
        return Action::make('cerrar')
            ->label('Cerrar')
            ->icon(Heroicon::OutlinedCalendarDays)
            ->color('gray')
            ->authorize('cerrar')
            ->visible(fn (Tarifa $record): bool => $record->vigente_hasta === null)
            ->schema([
                DatePicker::make('vigente_hasta')->label('Último día de vigencia')->required(),
            ])
            ->action(fn (Action $action, Tarifa $record, array $data) => AccionDeNegocio::ejecutar(
                $action,
                fn (): Tarifa => app(CerrarTarifa::class)($record->id, CarbonImmutable::parse($data['vigente_hasta'])),
                'Tarifa cerrada',
            ));
    }
}
