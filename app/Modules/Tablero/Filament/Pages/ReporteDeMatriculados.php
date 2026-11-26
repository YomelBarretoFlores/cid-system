<?php

declare(strict_types=1);

namespace App\Modules\Tablero\Filament\Pages;

use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Tablero\Actions\GenerarReporteDeMatriculados;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Concerns\ReporteGeneralDelPanel;
use App\Shared\Ui\Filament\Clusters\Reportes;
use BackedEnum;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Contracts\HasTable;

final class ReporteDeMatriculados extends Page implements HasTable
{
    use ReporteGeneralDelPanel;

    #[\Override]
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUserGroup;

    #[\Override]
    protected static ?string $cluster = Reportes::class;

    #[\Override]
    protected static ?int $navigationSort = 1;

    #[\Override]
    protected static ?string $navigationLabel = 'Matriculados';

    #[\Override]
    protected static ?string $title = 'Matriculados';

    #[\Override]
    protected static ?string $slug = 'reporte-de-matriculados';

    #[\Override]
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
