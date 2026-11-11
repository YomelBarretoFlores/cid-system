<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Tablero\Actions\GenerarReporteDeCargaDocente;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use UnitEnum;

final class ReporteDeCargaDocente extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    #[\Override]
    protected static string|UnitEnum|null $navigationGroup = 'Docencia';

    #[\Override]
    protected static ?int $navigationSort = 5;

    #[\Override]
    protected static ?string $navigationLabel = 'Carga docente';

    #[\Override]
    protected static ?string $title = 'Carga docente';

    #[\Override]
    protected static ?string $slug = 'reporte-de-carga-docente';

    #[\Override]
    protected ?string $subheading = 'Horas asignadas y dictadas de cada docente en el periodo.';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoDocencia::Reportar->value) ?? false;
    }

    protected function generar(FiltrosDeReporte $filtros): ReporteGeneral
    {
        return app(GenerarReporteDeCargaDocente::class)($filtros);
    }
}
