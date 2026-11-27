<?php

declare(strict_types=1);

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Tablero\Actions\ExportarReporteGeneralPdf;
use App\Modules\Tablero\Actions\GenerarReporteEstadistico;
use App\Modules\Tablero\Actions\GenerarReporteMensual;
use App\Modules\Tablero\Data\ColumnaDeReporte;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Filament\Pages\ReporteEstadistico;
use App\Modules\Tablero\Filament\Pages\ReporteMensual;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Tests\Support\ReportesDelEstudiante;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->escenario = ReportesDelEstudiante::escenario();
    $this->grupo = $this->escenario['curso']['grupo'];
    $this->programa = $this->grupo->programa->nombre;
    $this->filtros = new FiltrosDeReporte(periodoId: $this->grupo->periodo_id);
});

it('counts each group by language and program, one row per level and cycle with its teacher, and the total', function (): void {
    $reporte = app(GenerarReporteEstadistico::class)($this->filtros);
    $seccion = $reporte->secciones[0];

    expect($reporte->titulo)->toBe('Reporte estadístico por idioma')
        ->and($reporte->formal)->toBeTrue()
        ->and($seccion->titulo)->toBe("Inglés · {$this->programa}")
        ->and(array_map(fn (ColumnaDeReporte $columna): string => $columna->nombre, $seccion->columnas))->toBe(['Nivel', 'Ciclo', 'Docente', 'Grupo', 'Inscritos', 'Aprobados', 'Desaprobados', 'NSP', 'Total'])
        ->and($seccion->filas)->toBe([['Básico', 1, 'Vega Ríos, Carlos', 'ING-1-M', 1, 0, 0, 0, 1]])
        ->and($seccion->totales)->toBe([['Total', null, null, null, 1, 0, 0, 0, 1]]);

    ReportesDelEstudiante::cerrarActa($this->escenario['curso']);

    expect(app(GenerarReporteEstadistico::class)($this->filtros)->secciones[0]->filas)->toBe([['Básico', 1, 'Vega Ríos, Carlos', 'ING-1-M', 0, 0, 1, 0, 1]]);
});

it('sums passed and failed students per language for each program and overall', function (): void {
    ReportesDelEstudiante::cerrarActa($this->escenario['curso']);

    $seccion = app(GenerarReporteMensual::class)($this->filtros)->secciones[0];

    expect(array_map(fn (ColumnaDeReporte $columna): string => $columna->nombre, $seccion->columnas))->toBe([
        'Idioma', "{$this->programa} · Aprobados", "{$this->programa} · Desaprobados", "{$this->programa} · Total", 'Aprobados', 'Desaprobados', 'Total',
    ])
        ->and($seccion->filas)->toBe([['Inglés', 0, 1, 1, 0, 1, 1]])
        ->and($seccion->totales)->toBe([['Total', 0, 1, 1, 0, 1, 1]]);
});

it('prints the statistical report on the formal register sheet, with the subtotals', function (): void {
    $pdf = app(ExportarReporteGeneralPdf::class)(app(GenerarReporteEstadistico::class)($this->filtros));
    $html = view($pdf->viewName, $pdf->viewData)->render();

    expect($pdf->orientation)->toBe('Portrait')
        ->and($html)->toContain('REPORTE ESTADÍSTICO POR IDIOMA', 'VICERRECTORADO ACADÉMICO', 'IDIOMA - MODALIDAD: INGLÉS - '.mb_strtoupper($this->programa), 'Vega Ríos, Carlos', 'class="total"');
});

it('opens the statistical and monthly reports only with the actas permission', function (string $pagina): void {
    $this->actingAs(User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION));
    expect($pagina::canAccess())->toBeFalse();

    $this->actingAs(User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Actas->value]));
    expect($pagina::canAccess())->toBeTrue();
})->with([ReporteEstadistico::class, ReporteMensual::class]);
