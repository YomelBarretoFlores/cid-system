<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CostosPorHora;

use App\Modules\Docencia\Actions\EliminarCostoPorHora;
use App\Modules\Docencia\Filament\Resources\CostosPorHora\Pages\ListCostosPorHora;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Shared\Support\Filament\AccionDeNegocio;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class CostoPorHoraResource extends Resource
{
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = CostoPorHora::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Docencia';

    #[\Override]
    protected static ?int $navigationSort = 6;

    #[\Override]
    protected static ?string $navigationLabel = 'Costos por hora';

    #[\Override]
    protected static ?string $modelLabel = 'costo por hora';

    #[\Override]
    protected static ?string $pluralModelLabel = 'costos por hora';

    #[\Override]
    protected static ?string $slug = 'costos-por-hora';

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query): Builder => $query->with(['gradoAcademico', 'condicionLaboral']))
            ->columns([
                TextColumn::make('categoria')->label('Grado académico')->state(fn (CostoPorHora $record): string => $record->categoria()),
                TextColumn::make('costo_hora')->label('Costo por hora')->money('PEN'),
                TextColumn::make('vigente_desde')->label('Desde')->date('d/m/Y')->sortable(),
                TextColumn::make('vigente_hasta')->label('Hasta')->date('d/m/Y')->placeholder('Sin fecha de cierre'),
            ])
            ->defaultSort('vigente_desde', 'desc')
            ->recordActions([
                Action::make('eliminar')
                    ->label('Eliminar')
                    ->icon(Heroicon::OutlinedTrash)
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalDescription('Solo se elimina un costo que aún no entra en vigencia; el costo que cerró vuelve a quedar abierto.')
                    ->authorize('delete')
                    ->visible(fn (CostoPorHora $record): bool => EliminarCostoPorHora::aunNoVigente($record))
                    ->action(fn (Action $action, CostoPorHora $record) => AccionDeNegocio::ejecutar(
                        $action,
                        fn () => app(EliminarCostoPorHora::class)($record->id),
                        'Costo por hora eliminado',
                    )),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCostosPorHora::route('/'),
        ];
    }
}
