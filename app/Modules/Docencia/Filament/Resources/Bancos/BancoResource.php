<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\Bancos;

use App\Modules\Docencia\Filament\Resources\Bancos\Pages\CreateBanco;
use App\Modules\Docencia\Filament\Resources\Bancos\Pages\EditBanco;
use App\Modules\Docencia\Filament\Resources\Bancos\Pages\ListBancos;
use App\Modules\Docencia\Models\Banco;
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

final class BancoResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    public const string REPETIDO = 'Ya existe un banco con ese nombre.';

    public const string TABLA = 'doc_bancos';

    #[\Override]
    protected static ?string $model = Banco::class;

    #[\Override]
    protected static ?string $slug = 'bancos';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingLibrary;

    #[\Override]
    protected static ?string $cluster = Configuracion::class;

    protected static string|UnitEnum|null $navigationGroup = 'Docencia';

    #[\Override]
    protected static ?int $navigationSort = 15;

    #[\Override]
    protected static ?string $navigationLabel = 'Bancos';

    #[\Override]
    protected static ?string $modelLabel = 'banco';

    #[\Override]
    protected static ?string $pluralModelLabel = 'bancos';

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
            'index' => ListBancos::route('/'),
            'create' => CreateBanco::route('/create'),
            'edit' => EditBanco::route('/{record}/edit'),
        ];
    }
}
