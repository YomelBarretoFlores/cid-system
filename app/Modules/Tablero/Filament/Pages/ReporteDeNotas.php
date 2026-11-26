<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Tablero\Actions\GenerarReporteDeNotas;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use App\Shared\Ui\Filament\Clusters\Reportes;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;

final class ReporteDeNotas extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedAcademicCap;

    #[\Override]
    protected static ?string $cluster = Reportes::class;

    #[\Override]
    protected static ?int $navigationSort = 3;

    #[\Override]
    protected static ?string $navigationLabel = 'Notas por periodo';

    #[\Override]
    protected static ?string $title = 'Notas por periodo';

    #[\Override]
    protected static ?string $slug = 'reporte-de-notas';

    #[\Override]
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
