<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Tablero\Actions\GenerarReporteMensual;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use App\Shared\Ui\Filament\Clusters\Reportes;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;

final class ReporteMensual extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCalendarDays;

    #[\Override]
    protected static ?string $cluster = Reportes::class;

    #[\Override]
    protected static ?int $navigationSort = 7;

    #[\Override]
    protected static ?string $navigationLabel = 'Mensual por idioma';

    #[\Override]
    protected static ?string $title = 'Mensual por idioma';

    #[\Override]
    protected static ?string $slug = 'reporte-mensual';

    #[\Override]
    protected ?string $subheading = 'Aprobados y desaprobados de cada idioma por programa y en total.';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoCertificacion::Actas->value) ?? false;
    }

    protected function generar(FiltrosDeReporte $filtros): ReporteGeneral
    {
        return app(GenerarReporteMensual::class)($filtros);
    }
}
