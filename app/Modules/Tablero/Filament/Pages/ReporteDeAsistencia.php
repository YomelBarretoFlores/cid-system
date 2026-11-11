<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Tablero\Actions\GenerarReporteDeAsistencia;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use UnitEnum;

final class ReporteDeAsistencia extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedClipboardDocumentCheck;

    protected static string|UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Asistencia por grupo';

    protected static ?string $title = 'Asistencia por grupo';

    protected static ?string $slug = 'reporte-de-asistencia';

    protected ?string $subheading = 'Clases programadas, dictadas y confirmadas de cada grupo, y la asistencia de cada estudiante.';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoDocencia::Reportar->value) ?? false;
    }

    protected function generar(FiltrosDeReporte $filtros): ReporteGeneral
    {
        return app(GenerarReporteDeAsistencia::class)($filtros);
    }
}
