<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\Habilitaciones;

use App\Modules\Matricula\Filament\Resources\Habilitaciones\Pages\ListHabilitaciones;
use App\Modules\Matricula\Filament\Resources\Habilitaciones\Tables\HabilitacionesTable;
use App\Modules\Matricula\Models\Habilitacion;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class HabilitacionResource extends Resource
{
    use TitulosEnMayusculaInicial;

    protected static ?string $model = Habilitacion::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    protected static string|UnitEnum|null $navigationGroup = 'Atención';

    protected static ?int $navigationSort = 3;

    protected static ?string $modelLabel = 'ciclo habilitado';

    protected static ?string $pluralModelLabel = 'ciclos habilitados';

    protected static ?string $slug = 'ciclos-habilitados';

    protected static bool $isGloballySearchable = false;

    public static function table(Table $table): Table
    {
        return HabilitacionesTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListHabilitaciones::route('/'),
        ];
    }
}
