<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\MediosDePago;

use App\Modules\Pagos\Filament\Resources\MediosDePago\Pages\CreateMedioDePago;
use App\Modules\Pagos\Filament\Resources\MediosDePago\Pages\EditMedioDePago;
use App\Modules\Pagos\Filament\Resources\MediosDePago\Pages\ListMediosDePago;
use App\Modules\Pagos\Models\MedioDePago;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use App\Shared\Ui\Filament\Clusters\Configuracion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class MedioDePagoResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = MedioDePago::class;

    #[\Override]
    protected static ?string $slug = 'medios-de-pago';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Pagos';

    #[\Override]
    protected static ?int $navigationSort = 2;

    #[\Override]
    protected static ?string $navigationLabel = 'Medios de pago';

    #[\Override]
    protected static ?string $modelLabel = 'medio de pago';

    #[\Override]
    protected static ?string $pluralModelLabel = 'medios de pago';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return CatalogoEnElPanel::formulario($schema, 'Ya existe un medio de pago con ese nombre.');
    }

    public static function table(Table $table): Table
    {
        return CatalogoEnElPanel::tabla($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListMediosDePago::route('/'),
            'create' => CreateMedioDePago::route('/create'),
            'edit' => EditMedioDePago::route('/{record}/edit'),
        ];
    }
}
