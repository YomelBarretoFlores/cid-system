<?php

declare(strict_types=1);

namespace App\Modules\Personas\Filament\Resources\Personas;

use App\Modules\Personas\Filament\Resources\Personas\Pages\CreatePersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\EditPersona;
use App\Modules\Personas\Filament\Resources\Personas\Pages\ListPersonas;
use App\Modules\Personas\Filament\Resources\Personas\Pages\ViewPersona;
use App\Modules\Personas\Filament\Resources\Personas\Schemas\PersonaForm;
use App\Modules\Personas\Filament\Resources\Personas\Schemas\PersonaInfolist;
use App\Modules\Personas\Filament\Resources\Personas\Tables\PersonasTable;
use App\Modules\Personas\Models\Persona;
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

final class PersonaResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Persona::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Matrícula';

    #[\Override]
    protected static ?int $navigationSort = 1;

    #[\Override]
    protected static ?string $navigationLabel = 'Personas';

    #[\Override]
    protected static ?string $modelLabel = 'persona';

    #[\Override]
    protected static ?string $pluralModelLabel = 'personas';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'etiqueta';

    public static function form(Schema $schema): Schema
    {
        return PersonaForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return PersonaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PersonasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPersonas::route('/'),
            'create' => CreatePersona::route('/create'),
            'view' => ViewPersona::route('/{record}'),
            'edit' => EditPersona::route('/{record}/edit'),
        ];
    }

    // Same match as the people list (document prefix or names); Filament's default would query the computed label.
    /**
     * @param  Builder<Model>  $query
     */
    protected static function applyGlobalSearchAttributeConstraints(Builder $query, string $search): void
    {
        $query->whereIn('id', Persona::query()->coincideCon($search)->select('id'));
    }

    /**
     * @return Builder<Model>
     */
    public static function getGlobalSearchEloquentQuery(): Builder
    {
        return parent::getGlobalSearchEloquentQuery()->with(['vinculo', 'tipoDeDocumento']);
    }

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return $record instanceof Persona ? ['Vínculo' => $record->vinculo->nombre] : [];
    }
}
