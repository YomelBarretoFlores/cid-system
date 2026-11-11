<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Idiomas;

use App\Modules\Academico\Filament\Resources\Idiomas\Pages\CreateIdioma;
use App\Modules\Academico\Filament\Resources\Idiomas\Pages\EditIdioma;
use App\Modules\Academico\Filament\Resources\Idiomas\Pages\ListIdiomas;
use App\Modules\Academico\Filament\Resources\Idiomas\RelationManagers\CiclosRelationManager;
use App\Modules\Academico\Filament\Resources\Idiomas\Schemas\IdiomaForm;
use App\Modules\Academico\Filament\Resources\Idiomas\Tables\IdiomasTable;
use App\Modules\Academico\Models\Idioma;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class IdiomaResource extends Resource
{
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Idioma::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLanguage;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Oferta académica';

    #[\Override]
    protected static ?int $navigationSort = 2;

    #[\Override]
    protected static ?string $navigationLabel = 'Idiomas y ciclos';

    #[\Override]
    protected static ?string $modelLabel = 'idioma';

    #[\Override]
    protected static ?string $pluralModelLabel = 'idiomas';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return IdiomaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return IdiomasTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CiclosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListIdiomas::route('/'),
            'create' => CreateIdioma::route('/create'),
            'edit' => EditIdioma::route('/{record}/edit'),
        ];
    }
}
