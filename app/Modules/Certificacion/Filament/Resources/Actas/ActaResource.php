<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Filament\Resources\Actas;

use App\Modules\Certificacion\Filament\Resources\Actas\Pages\ListActas;
use App\Modules\Certificacion\Filament\Resources\Actas\Pages\ViewActa;
use App\Modules\Certificacion\Filament\Resources\Actas\Schemas\ActaInfolist;
use App\Modules\Certificacion\Filament\Resources\Actas\Tables\ActasTable;
use App\Modules\Certificacion\Models\Acta;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class ActaResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Acta::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentCheck;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Certificación';

    #[\Override]
    protected static ?int $navigationSort = 2;

    #[\Override]
    protected static ?string $modelLabel = 'acta final';

    #[\Override]
    protected static ?string $pluralModelLabel = 'actas finales';

    #[\Override]
    protected static ?string $navigationLabel = 'Actas finales';

    #[\Override]
    protected static ?string $slug = 'actas-finales';

    #[\Override]
    protected static bool $isGloballySearchable = false;

    public static function infolist(Schema $schema): Schema
    {
        return ActaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ActasTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListActas::route('/'),
            'view' => ViewActa::route('/{record}'),
        ];
    }
}
