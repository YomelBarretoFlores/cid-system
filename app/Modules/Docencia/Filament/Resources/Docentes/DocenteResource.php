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
use App\Modules\Personas\Contracts\FichaDePersona;
use App\Modules\Personas\Contracts\SubconsultaDePersonas;
use App\Modules\Personas\Data\FichaDeEstudiante;
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

final class DocenteResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Docente::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Docencia';

    #[\Override]
    protected static ?int $navigationSort = 1;

    #[\Override]
    protected static ?string $navigationLabel = 'Docentes';

    #[\Override]
    protected static ?string $modelLabel = 'docente';

    #[\Override]
    protected static ?string $pluralModelLabel = 'docentes';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'codigo';

    #[\Override]
    protected static int $globalSearchResultsLimit = 10;

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

    // Staff look for a teacher by name or document more often than by code; the names live in Personas.
    /**
     * @param  Builder<Model>  $query
     */
    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        $query->where(fn (Builder $coincidencia): Builder => $coincidencia
            ->whereLike('codigo', '%'.addcslashes(trim($search), '\\%_').'%')
            ->orWhereIn('persona_id', app(SubconsultaDePersonas::class)($search)));
    }

    public static function getGlobalSearchResultTitle(Model $record): string
    {
        return $record instanceof Docente ? self::fichaDe($record)->nombreCompleto : '';
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return $record instanceof Docente ? ['Código' => $record->codigo, 'Documento' => self::fichaDe($record)->documento] : [];
    }

    private static function fichaDe(Docente $docente): FichaDeEstudiante
    {
        return app(FichaDePersona::class)($docente->persona_id);
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
