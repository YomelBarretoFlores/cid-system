<?php

declare(strict_types=1);

namespace App\Modules\Academico\Filament\Resources\Programas;

use App\Modules\Academico\Filament\Resources\Programas\Pages\CreatePrograma;
use App\Modules\Academico\Filament\Resources\Programas\Pages\EditPrograma;
use App\Modules\Academico\Filament\Resources\Programas\Pages\ListProgramas;
use App\Modules\Academico\Filament\Resources\Programas\Schemas\ProgramaForm;
use App\Modules\Academico\Filament\Resources\Programas\Tables\ProgramasTable;
use App\Modules\Academico\Models\Programa;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ProgramaResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Programa::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Oferta académica';

    #[\Override]
    protected static ?int $navigationSort = 6;

    #[\Override]
    protected static ?string $navigationLabel = 'Programas';

    #[\Override]
    protected static ?string $modelLabel = 'programa';

    #[\Override]
    protected static ?string $pluralModelLabel = 'programas';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return ProgramaForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ProgramasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListProgramas::route('/'),
            'create' => CreatePrograma::route('/create'),
            'edit' => EditPrograma::route('/{record}/edit'),
        ];
    }
}
