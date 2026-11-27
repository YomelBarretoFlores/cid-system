<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Tablero\Actions\GenerarReporteFinanciero;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use App\Shared\Ui\Filament\Clusters\Reportes;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;

final class ReporteFinanciero extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    #[\Override]
    protected static ?string $cluster = Reportes::class;

    #[\Override]
    protected static ?int $navigationSort = 9;

    #[\Override]
    protected static ?string $navigationLabel = 'Financiero por idioma';

    #[\Override]
    protected static ?string $title = 'Financiero por idioma';

    #[\Override]
    protected static ?string $slug = 'reporte-financiero';

    #[\Override]
    protected ?string $subheading = 'Pagos de matrícula por idioma, programa y nivel, con comprobante y monto.';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoPago::Reportar->value) ?? false;
    }

    protected function generar(FiltrosDeReporte $filtros): ReporteGeneral
    {
        return app(GenerarReporteFinanciero::class)($filtros);
    }
}
