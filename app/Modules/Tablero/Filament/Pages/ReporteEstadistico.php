<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Tablero\Actions\GenerarReporteEstadistico;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use App\Shared\Ui\Filament\Clusters\Reportes;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;

final class ReporteEstadistico extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedChartBar;

    #[\Override]
    protected static ?string $cluster = Reportes::class;

    #[\Override]
    protected static ?int $navigationSort = 6;

    #[\Override]
    protected static ?string $navigationLabel = 'Estadístico por idioma';

    #[\Override]
    protected static ?string $title = 'Estadístico por idioma';

    #[\Override]
    protected static ?string $slug = 'reporte-estadistico';

    #[\Override]
    protected ?string $subheading = 'Por idioma y programa, los grupos de cada nivel con su docente, inscritos, aprobados, desaprobados y totales.';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoCertificacion::Actas->value) ?? false;
    }

    protected function generar(FiltrosDeReporte $filtros): ReporteGeneral
    {
        return app(GenerarReporteEstadistico::class)($filtros);
    }
}
