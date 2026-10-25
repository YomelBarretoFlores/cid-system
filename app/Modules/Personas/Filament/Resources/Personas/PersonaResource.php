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
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class PersonaResource extends Resource
{
    use TitulosEnMayusculaInicial;

    protected static ?string $model = Persona::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedIdentification;

    protected static string|UnitEnum|null $navigationGroup = 'Personas';

    protected static ?string $navigationLabel = 'Personas';

    protected static ?string $modelLabel = 'persona';

    protected static ?string $pluralModelLabel = 'personas';

    protected static ?string $recordTitleAttribute = 'etiqueta';

    // Ley 29733: personal data stays inside this screen, out of the panel-wide search.
    protected static bool $isGloballySearchable = false;

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
}
