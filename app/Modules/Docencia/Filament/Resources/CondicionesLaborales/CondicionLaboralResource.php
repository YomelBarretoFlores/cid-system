<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CondicionesLaborales;

use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages\CreateCondicionLaboral;
use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages\EditCondicionLaboral;
use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages\ListCondicionesLaborales;
use App\Modules\Docencia\Models\CondicionLaboral;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class CondicionLaboralResource extends Resource
{
    use TitulosEnMayusculaInicial;

    public const string REPETIDO = 'Ya existe una condición laboral con ese nombre.';

    public const string TABLA = 'doc_condiciones_laborales';

    protected static ?string $model = CondicionLaboral::class;

    protected static ?string $slug = 'condiciones-laborales';

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 5;

    protected static ?string $navigationLabel = 'Condiciones laborales';

    protected static ?string $modelLabel = 'condición laboral';

    protected static ?string $pluralModelLabel = 'condiciones laborales';

    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return CatalogoEnElPanel::formulario($schema, self::REPETIDO);
    }

    public static function table(Table $table): Table
    {
        return CatalogoEnElPanel::tabla($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCondicionesLaborales::route('/'),
            'create' => CreateCondicionLaboral::route('/create'),
            'edit' => EditCondicionLaboral::route('/{record}/edit'),
        ];
    }
}
