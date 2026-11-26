<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos\RelationManagers;

use App\Modules\Academico\Contracts\CatalogoDeProgramas;
use App\Modules\Academico\Data\ProgramaDelCatalogo;
use App\Modules\Academico\Enums\NivelCurricular;
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
    #[\Override]
    protected static string $relationship = 'tarifas';

    #[\Override]
    protected static ?string $title = 'Tarifas';

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('monto')
            ->columns([
                TextColumn::make('tipo_vinculo')->label('Vínculo')->formatStateUsing(fn (TipoVinculo $state): string => $state->etiqueta()),
                TextColumn::make('nivel')->label('Nivel')->formatStateUsing(fn (?NivelCurricular $state): string => $state?->etiqueta() ?? 'Todos')->placeholder('Todos'),
                TextColumn::make('programa_id')->label('Programa')->formatStateUsing(fn (?int $state): string => $state === null ? 'Todos' : ($this->programas(soloActivos: false)[$state] ?? 'Todos'))->placeholder('Todos'),
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
            ->modalDescription('Si hay una tarifa abierta para el mismo vínculo, nivel y programa, se cierra el día anterior al inicio de la nueva. Deja nivel y programa vacíos para una tarifa que vale para todos.')
            ->schema([
                Select::make('tipo_vinculo')
                    ->label('Vínculo con la UNASAM')
                    ->options(collect(TipoVinculo::cases())->mapWithKeys(fn (TipoVinculo $vinculo): array => [$vinculo->value => $vinculo->etiqueta()])->all())
                    ->required(),
                Select::make('nivel')
                    ->label('Nivel')
                    ->placeholder('Todos los niveles')
                    ->options(array_column(array_map(fn (NivelCurricular $nivel): array => [$nivel->value, $nivel->etiqueta()], NivelCurricular::cases()), 1, 0)),
                Select::make('programa_id')
                    ->label('Programa')
                    ->placeholder('Todos los programas')
                    ->options(fn (): array => $this->programas(soloActivos: true)),
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
                    NivelCurricular::tryFrom((string) ($data['nivel'] ?? '')),
                    filled($data['programa_id'] ?? null) ? (int) $data['programa_id'] : null,
                ),
                'Tarifa registrada',
            ));
    }

    /**
     * @return array<int, string>
     */
    private function programas(bool $soloActivos): array
    {
        $programas = array_filter(app(CatalogoDeProgramas::class)(), fn (ProgramaDelCatalogo $programa): bool => $programa->activo || ! $soloActivos);

        return array_column(array_map(fn (ProgramaDelCatalogo $programa): array => [$programa->id, $programa->nombre], $programas), 1, 0);
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
