<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Filament\Resources\CuentasDocentes;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\CuentasDocentes\Pages\ListCuentasDocentes;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class CuentaDocenteResource extends Resource
{
    use ClaveDeRegistroValidada;

    #[\Override]
    protected static ?string $model = Docente::class;

    #[\Override]
    protected static ?string $slug = 'cuentas-de-docentes';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedKey;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Seguridad';

    #[\Override]
    protected static ?int $navigationSort = 2;

    #[\Override]
    protected static ?string $navigationLabel = 'Cuentas de docentes';

    #[\Override]
    protected static ?string $modelLabel = 'cuenta de docente';

    #[\Override]
    protected static ?string $pluralModelLabel = 'Cuentas de docentes';

    #[\Override]
    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoDocencia::Gestionar->value) === true;
    }

    public static function table(Table $table): Table
    {
        return TablaDeCuentasDocentes::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCuentasDocentes::route('/'),
        ];
    }
}
