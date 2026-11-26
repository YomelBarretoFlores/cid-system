<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Filament\Resources\Conceptos;

use App\Modules\Pagos\Filament\Resources\Conceptos\Pages\CreateConcepto;
use App\Modules\Pagos\Filament\Resources\Conceptos\Pages\EditConcepto;
use App\Modules\Pagos\Filament\Resources\Conceptos\Pages\ListConceptos;
use App\Modules\Pagos\Filament\Resources\Conceptos\RelationManagers\TarifasRelationManager;
use App\Modules\Pagos\Filament\Resources\Conceptos\Schemas\ConceptoForm;
use App\Modules\Pagos\Filament\Resources\Conceptos\Tables\ConceptosTable;
use App\Modules\Pagos\Models\Concepto;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use App\Shared\Ui\Filament\Clusters\Configuracion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ConceptoResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Concepto::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Pagos';

    #[\Override]
    protected static ?int $navigationSort = 1;

    #[\Override]
    protected static ?string $navigationLabel = 'Conceptos y tarifas';

    #[\Override]
    protected static ?string $modelLabel = 'concepto';

    #[\Override]
    protected static ?string $pluralModelLabel = 'conceptos TUPA/TUSNE';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return ConceptoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ConceptosTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            TarifasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListConceptos::route('/'),
            'create' => CreateConcepto::route('/create'),
            'edit' => EditConcepto::route('/{record}/edit'),
        ];
    }
}
