<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\Tramites;

use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ListTramites;
use App\Modules\Tramites\Filament\Resources\Tramites\Pages\ViewTramite;
use App\Modules\Tramites\Filament\Resources\Tramites\Schemas\TramiteInfolist;
use App\Modules\Tramites\Filament\Resources\Tramites\Tables\TramitesTable;
use App\Modules\Tramites\Models\Tramite;
use App\Shared\Support\Filament\ContadorDePendientes;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use UnitEnum;

final class TramiteResource extends Resource
{
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = Tramite::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedInboxStack;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Trámites';

    #[\Override]
    protected static ?int $navigationSort = 1;

    #[\Override]
    protected static ?string $navigationLabel = 'Trámites';

    #[\Override]
    protected static ?string $modelLabel = 'trámite';

    #[\Override]
    protected static ?string $pluralModelLabel = 'trámites';

    #[\Override]
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

    public static function getGlobalSearchResultDetails(Model $record): array
    {
        return $record instanceof Tramite ? [
            'Tipo' => $record->tipo_nombre,
            'Estado' => $record->estado->etiqueta(),
        ] : [];
    }

    public static function getNavigationBadge(): ?string
    {
        return ContadorDePendientes::de(Tramite::query()->where('estado', EstadoTramite::EnProceso));
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Trámites por atender';
    }
}
