<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Tablero\Actions\GenerarReporteAdministrativo;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use App\Shared\Ui\Filament\Clusters\Reportes;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;

final class ReporteAdministrativo extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentList;

    #[\Override]
    protected static ?string $cluster = Reportes::class;

    #[\Override]
    protected static ?int $navigationSort = 8;

    #[\Override]
    protected static ?string $navigationLabel = 'Administrativo por idioma';

    #[\Override]
    protected static ?string $title = 'Administrativo por idioma';

    #[\Override]
    protected static ?string $slug = 'reporte-administrativo';

    #[\Override]
    protected ?string $subheading = 'Matrículas de cada grupo según el vínculo de la tarifa pagada y el lugar de pago.';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoPago::Reportar->value) ?? false;
    }

    protected function generar(FiltrosDeReporte $filtros): ReporteGeneral
    {
        return app(GenerarReporteAdministrativo::class)($filtros);
    }
}
