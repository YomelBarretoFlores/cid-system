<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CondicionesLaborales;

use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages\CreateCondicionLaboral;
use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages\EditCondicionLaboral;
use App\Modules\Docencia\Filament\Resources\CondicionesLaborales\Pages\ListCondicionesLaborales;
use App\Modules\Docencia\Models\CondicionLaboral;
use App\Shared\Support\Filament\CatalogoEnElPanel;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use App\Shared\Ui\Filament\Clusters\Configuracion;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class CondicionLaboralResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    public const string REPETIDO = 'Ya existe una condición laboral con ese nombre.';

    public const string TABLA = 'doc_condiciones_laborales';

    #[\Override]
    protected static ?string $model = CondicionLaboral::class;

    #[\Override]
    protected static ?string $slug = 'condiciones-laborales';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Docencia';

    #[\Override]
    protected static ?int $navigationSort = 6;

    #[\Override]
    protected static ?string $navigationLabel = 'Condiciones laborales';

    #[\Override]
    protected static ?string $modelLabel = 'condición laboral';

    #[\Override]
    protected static ?string $pluralModelLabel = 'condiciones laborales';

    #[\Override]
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
