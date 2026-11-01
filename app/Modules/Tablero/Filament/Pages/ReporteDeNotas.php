<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Tablero\Actions\GenerarReporteDeNotas;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use UnitEnum;

final class ReporteDeNotas extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    protected static string|UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationLabel = 'Notas por periodo';

    protected static ?string $title = 'Notas por periodo';

    protected static ?string $slug = 'reporte-de-notas';

    protected ?string $subheading = 'Estado del acta, aprobados, desaprobados, NSP y promedio de cada grupo.';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoCertificacion::Actas->value) ?? false;
    }

    protected function generar(FiltrosDeReporte $filtros): ReporteGeneral
    {
        return app(GenerarReporteDeNotas::class)($filtros);
    }
}
