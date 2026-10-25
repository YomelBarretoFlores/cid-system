<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Filament\Resources\TiposTramite;

use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\CreateTipoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\EditTipoTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Pages\ListTiposTramite;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Schemas\TipoTramiteForm;
use App\Modules\Tramites\Filament\Resources\TiposTramite\Tables\TiposTramiteTable;
use App\Modules\Tramites\Models\TipoTramite;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class TipoTramiteResource extends Resource
{
    use TitulosEnMayusculaInicial;

    protected static ?string $model = TipoTramite::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAdjustmentsHorizontal;

    protected static string|UnitEnum|null $navigationGroup = 'Configuración';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Tipos de trámite';

    protected static ?string $modelLabel = 'tipo de trámite';

    protected static ?string $pluralModelLabel = 'tipos de trámite';

    protected static ?string $slug = 'tipos-de-tramite';

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
