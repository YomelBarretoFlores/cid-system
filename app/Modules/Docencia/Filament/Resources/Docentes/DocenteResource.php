<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Docentes;

use App\Modules\Docencia\Filament\Resources\Docentes\Pages\CreateDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\EditDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ListDocentes;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ViewDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\RelationManagers\CargasRelationManager;
use App\Modules\Docencia\Filament\Resources\Docentes\RelationManagers\DisponibilidadesRelationManager;
use App\Modules\Docencia\Filament\Resources\Docentes\Schemas\DocenteForm;
use App\Modules\Docencia\Filament\Resources\Docentes\Schemas\DocenteInfolist;
use App\Modules\Docencia\Filament\Resources\Docentes\Tables\DocentesTable;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class DocenteResource extends Resource
{
    use TitulosEnMayusculaInicial;

    protected static ?string $model = Docente::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Docencia';

    protected static ?string $navigationLabel = 'Docentes';

    protected static ?string $modelLabel = 'docente';

    protected static ?string $pluralModelLabel = 'docentes';

    protected static ?string $recordTitleAttribute = 'codigo';

    public static function form(Schema $schema): Schema
    {
        return DocenteForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return DocenteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return DocentesTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            CargasRelationManager::class,
            DisponibilidadesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListDocentes::route('/'),
            'create' => CreateDocente::route('/create'),
            'view' => ViewDocente::route('/{record}'),
            'edit' => EditDocente::route('/{record}/edit'),
        ];
    }
}
