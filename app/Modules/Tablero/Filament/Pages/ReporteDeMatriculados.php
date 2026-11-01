<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Tablero\Actions\GenerarReporteDeMatriculados;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;
use UnitEnum;

final class ReporteDeMatriculados extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    protected static string|UnitEnum|null $navigationGroup = 'Reportes';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationLabel = 'Matriculados';

    protected static ?string $title = 'Matriculados';

    protected static ?string $slug = 'reporte-de-matriculados';

    protected ?string $subheading = 'Estudiantes matriculados del periodo por grupo, con su condición, estado y vínculo.';

    public static function canAccess(): bool
    {
        return auth()->user()?->can(PermisoMatricula::Ver->value) ?? false;
    }

    protected function generar(FiltrosDeReporte $filtros): ReporteGeneral
    {
        return app(GenerarReporteDeMatriculados::class)($filtros);
    }
}
