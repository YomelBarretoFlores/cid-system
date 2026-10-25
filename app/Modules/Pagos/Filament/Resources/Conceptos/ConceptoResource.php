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
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ConceptoResource extends Resource
{
    use TitulosEnMayusculaInicial;

    protected static ?string $model = Concepto::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Conceptos y tarifas';

    protected static ?string $modelLabel = 'concepto';

    protected static ?string $pluralModelLabel = 'conceptos TUPA/TUSNE';

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
