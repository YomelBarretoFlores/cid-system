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
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class GrupoResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Grupo::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Oferta académica';

    #[\Override]
    protected static ?int $navigationSort = 3;

    #[\Override]
    protected static ?string $navigationLabel = 'Grupos';

    #[\Override]
    protected static ?string $modelLabel = 'grupo';

    #[\Override]
    protected static ?string $pluralModelLabel = 'grupos';

    #[\Override]
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

    // Codes repeat across periods, so each result names its language, cycle and period.
    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return $record instanceof Grupo ? [
            'Idioma' => $record->ciclo->idioma->nombre.' · '.$record->ciclo->nombre,
            'Periodo' => $record->periodo->nombre,
        ] : [];
    }

    /**
     * @return Builder<Model>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['periodo', 'ciclo.idioma']);
    }
}
