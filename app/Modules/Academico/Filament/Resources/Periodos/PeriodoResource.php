<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Periodos;

use App\Modules\Academico\Filament\Resources\Periodos\Pages\CreatePeriodo;
use App\Modules\Academico\Filament\Resources\Periodos\Pages\EditPeriodo;
use App\Modules\Academico\Filament\Resources\Periodos\Pages\ListPeriodos;
use App\Modules\Academico\Filament\Resources\Periodos\Schemas\PeriodoForm;
use App\Modules\Academico\Filament\Resources\Periodos\Tables\PeriodosTable;
use App\Modules\Academico\Models\Periodo;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PeriodoResource extends Resource
{
    protected static ?string $model = Periodo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDateRange;

    protected static string|UnitEnum|null $navigationGroup = 'Oferta académica';

    protected static ?string $navigationLabel = 'Periodos';

    protected static ?string $modelLabel = 'periodo';

    protected static ?string $pluralModelLabel = 'periodos';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return PeriodoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PeriodosTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPeriodos::route('/'),
            'create' => CreatePeriodo::route('/create'),
            'edit' => EditPeriodo::route('/{record}/edit'),
        ];
    }
}
