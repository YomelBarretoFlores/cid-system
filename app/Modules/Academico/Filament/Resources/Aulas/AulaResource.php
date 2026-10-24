<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Aulas;

use App\Modules\Academico\Filament\Resources\Aulas\Pages\CreateAula;
use App\Modules\Academico\Filament\Resources\Aulas\Pages\EditAula;
use App\Modules\Academico\Filament\Resources\Aulas\Pages\ListAulas;
use App\Modules\Academico\Filament\Resources\Aulas\Schemas\AulaForm;
use App\Modules\Academico\Filament\Resources\Aulas\Tables\AulasTable;
use App\Modules\Academico\Models\Aula;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class AulaResource extends Resource
{
    protected static ?string $model = Aula::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice2;

    protected static string|UnitEnum|null $navigationGroup = 'Oferta académica';

    protected static ?string $navigationLabel = 'Aulas';

    protected static ?string $modelLabel = 'aula';

    protected static ?string $pluralModelLabel = 'aulas';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return AulaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return AulasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListAulas::route('/'),
            'create' => CreateAula::route('/create'),
            'edit' => EditAula::route('/{record}/edit'),
        ];
    }
}
