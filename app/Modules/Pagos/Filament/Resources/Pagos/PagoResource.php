<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Pagos;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\CreatePago;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ListPagos;
use App\Modules\Pagos\Filament\Resources\Pagos\Pages\ViewPago;
use App\Modules\Pagos\Filament\Resources\Pagos\Schemas\PagoForm;
use App\Modules\Pagos\Filament\Resources\Pagos\Schemas\PagoInfolist;
use App\Modules\Pagos\Filament\Resources\Pagos\Tables\PagosTable;
use App\Modules\Pagos\Models\Pago;
use App\Shared\Support\Filament\ContadorDePendientes;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class PagoResource extends Resource
{
    use TitulosEnMayusculaInicial;

    protected static ?string $model = Pago::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static string|UnitEnum|null $navigationGroup = 'Atención';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'pago';

    protected static ?string $pluralModelLabel = 'pagos';

    protected static ?string $recordTitleAttribute = 'numero_operacion';

    public static function form(Schema $schema): Schema
    {
        return PagoForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PagoInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PagosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPagos::route('/'),
            'create' => CreatePago::route('/registrar'),
            'view' => ViewPago::route('/{record}'),
        ];
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return $record instanceof Pago ? [
            'Concepto' => $record->concepto->nombre,
            'Monto' => 'S/ '.number_format((float) $record->monto, 2),
            'Pagado el' => $record->fecha_pago->format('d/m/Y'),
            'Estado' => $record->estado->etiqueta(),
        ] : [];
    }

    /**
     * @return Builder<Model>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with('concepto');
    }

    public static function getNavigationBadge(): ?string
    {
        return ContadorDePendientes::de(Pago::query()->where('estado', EstadoPago::Pendiente));
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Pagos por validar';
    }
}
