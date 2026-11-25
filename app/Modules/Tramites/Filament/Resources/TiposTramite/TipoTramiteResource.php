<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\TiposTramite;

use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\CreateTipoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\EditTipoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\ListTiposTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Schemas\TipoTramiteForm;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Tables\TiposTramiteTable;
use App\Modules\Tramites\Models\TipoTramite;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class TipoTramiteResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = TipoTramite::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Catálogos';

    #[\Override]
    protected static ?int $navigationSort = 4;

    #[\Override]
    protected static ?string $navigationLabel = 'Tipos de trámite';

    #[\Override]
    protected static ?string $modelLabel = 'tipo de trámite';

    #[\Override]
    protected static ?string $pluralModelLabel = 'tipos de trámite';

    #[\Override]
    protected static ?string $slug = 'tipos-de-tramite';

    #[\Override]
    protected static ?string $recordTitleAttribute = 'nombre';

    public static function form(Schema $schema): Schema
    {
        return TipoTramiteForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return TiposTramiteTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListTiposTramite::route('/'),
            'create' => CreateTipoTramite::route('/create'),
            'edit' => EditTipoTramite::route('/{record}/edit'),
        ];
    }
}
