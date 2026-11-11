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

    #[\Override]
    protected static ?string $model = Habilitacion::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedArrowTrendingUp;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Matrícula';

    #[\Override]
    protected static ?int $navigationSort = 4;

    #[\Override]
    protected static ?string $modelLabel = 'ciclo habilitado';

    #[\Override]
    protected static ?string $pluralModelLabel = 'ciclos habilitados';

    #[\Override]
    protected static ?string $slug = 'ciclos-habilitados';

    #[\Override]
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
