<?php

declare(strict_types=1);

namespace App\Shared\Audit\Filament\Resources\Auditoria;

use App\Shared\Audit\Filament\Resources\Auditoria\Pages\ListRegistrosDeAuditoria;
use App\Shared\Audit\Filament\Resources\Auditoria\Pages\ViewRegistroDeAuditoria;
use App\Shared\Audit\Models\RegistroDeAuditoria;
use App\Shared\Support\Filament\ClaveDeRegistroValidada;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use UnitEnum;

final class RegistroDeAuditoriaResource extends Resource
{
    use ClaveDeRegistroValidada;
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = RegistroDeAuditoria::class;

    #[\Override]
    protected static ?string $slug = 'auditoria';

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Seguridad';

    #[\Override]
    protected static ?int $navigationSort = 4;

    #[\Override]
    protected static ?string $navigationLabel = 'Auditoría';

    #[\Override]
    protected static ?string $modelLabel = 'registro de auditoría';

    #[\Override]
    protected static ?string $pluralModelLabel = 'auditoría';

    #[\Override]
    protected static bool $isGloballySearchable = false;

    /**
     * @return Builder<RegistroDeAuditoria>
     */
    public static function getEloquentQuery(): Builder
    {
        return RegistroDeAuditoria::query()->with('usuario:id,name');
    }

    public static function infolist(Schema $schema): Schema
    {
        return RegistroDeAuditoriaInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return RegistrosDeAuditoriaTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListRegistrosDeAuditoria::route('/'),
            'view' => ViewRegistroDeAuditoria::route('/{record}'),
        ];
    }
}
