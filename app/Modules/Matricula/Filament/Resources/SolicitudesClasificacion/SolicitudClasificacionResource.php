<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion;

use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ListSolicitudesClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Pages\ViewSolicitudClasificacion;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Schemas\SolicitudClasificacionInfolist;
use App\Modules\Matricula\Filament\Resources\SolicitudesClasificacion\Tables\SolicitudesClasificacionTable;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Shared\Support\Filament\ContadorDePendientes;
use App\Shared\Support\Filament\TitulosEnMayusculaInicial;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

final class SolicitudClasificacionResource extends Resource
{
    use TitulosEnMayusculaInicial;

    #[\Override]
    protected static ?string $model = SolicitudClasificacion::class;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Matrícula';

    #[\Override]
    protected static ?int $navigationSort = 2;

    #[\Override]
    protected static ?string $modelLabel = 'solicitud de clasificación';

    #[\Override]
    protected static ?string $pluralModelLabel = 'solicitudes de clasificación';

    #[\Override]
    protected static ?string $slug = 'solicitudes-clasificacion';

    #[\Override]
    protected static bool $isGloballySearchable = false;

    public static function infolist(Schema $schema): Schema
    {
        return SolicitudClasificacionInfolist::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return SolicitudesClasificacionTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSolicitudesClasificacion::route('/'),
            'view' => ViewSolicitudClasificacion::route('/{record}'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        return ContadorDePendientes::de(SolicitudClasificacion::query()->where('estado', EstadoSolicitudClasificacion::PendienteEvaluacion));
    }

    public static function getNavigationBadgeTooltip(): string
    {
        return 'Solicitudes por evaluar';
    }
}
