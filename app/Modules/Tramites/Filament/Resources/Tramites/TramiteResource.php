<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\Tramites;

use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ListTramites;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ViewTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\Schemas\TramiteInfolist;
use App\Modules\Tramites\Filament\Resources\Tramites\Tables\TramitesTable;
use App\Modules\Tramites\Models\Tramite;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class TramiteResource extends Resource
{
    protected static ?string $model = Tramite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    protected static string|UnitEnum|null $navigationGroup = 'Trámites';

    protected static ?string $navigationLabel = 'Trámites';

    protected static ?string $modelLabel = 'trámite';

    protected static ?string $pluralModelLabel = 'Trámites';

    protected static bool $hasTitleCaseModelLabel = false;

    protected static ?string $recordTitleAttribute = 'codigo';

    public static function infolist(Schema $schema): Schema
    {
        return TramiteInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TramitesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTramites::route('/'),
            'view' => ViewTramite::route('/{record}'),
        ];
    }
}
