<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\FuentesDeConcepto;

use App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\Pages\CreateFuenteDeConcepto;
use App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\Pages\EditFuenteDeConcepto;
use App\Modules\Pagos\Filament\Resources\FuentesDeConcepto\Pages\ListFuentesDeConcepto;
use App\Modules\Pagos\Models\FuenteDeConcepto;
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

final class FuenteDeConceptoResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = FuenteDeConcepto::class;

    #[\Override]
    protected static ?string $slug = 'fuentes-de-conceptos';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBookOpen;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Pagos';

    #[\Override]
    protected static ?int $navigationSort = 3;

    #[\Override]
    protected static ?string $navigationLabel = 'Fuentes TUPA/TUSNE';

    #[\Override]
    protected static ?string $modelLabel = 'fuente';

    #[\Override]
    protected static ?string $pluralModelLabel = 'fuentes de conceptos';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return CatalogoEnElPanel::formulario($schema, 'Ya existe una fuente con ese nombre.');
    }

    public static function table(Table $table): Table
    {
        return CatalogoEnElPanel::tabla($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListFuentesDeConcepto::route('/'),
            'create' => CreateFuenteDeConcepto::route('/create'),
            'edit' => EditFuenteDeConcepto::route('/{record}/edit'),
        ];
    }
}
