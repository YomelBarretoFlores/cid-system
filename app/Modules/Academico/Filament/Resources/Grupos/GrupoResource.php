<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Grupos;

use App\Modules\Academico\Filament\Resources\Grupos\Pages\CreateGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\EditGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\ListGrupos;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\ViewGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\RelationManagers\HorariosRelationManager;
use App\Modules\Academico\Filament\Resources\Grupos\Schemas\GrupoForm;
use App\Modules\Academico\Filament\Resources\Grupos\Tables\GruposTable;
use App\Modules\Academico\Models\Grupo;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class GrupoResource extends Resource
{
    use TitulosEnMayusculaInicial;

    protected static ?string $model = Grupo::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Oferta académica';

    protected static ?string $navigationLabel = 'Grupos';

    protected static ?string $modelLabel = 'grupo';

    protected static ?string $pluralModelLabel = 'grupos';

    protected static ?string $recordTitleAttribute = 'codigo';

    public static function form(Schema $schema): Schema
    {
        return GrupoForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return GruposTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            HorariosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListGrupos::route('/'),
            'create' => CreateGrupo::route('/create'),
            'view' => ViewGrupo::route('/{record}'),
            'edit' => EditGrupo::route('/{record}/edit'),
        ];
    }
}
