<?php

declare(strict_types=1);

use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Docencia\Contracts\AsistenciaDeMatricula;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Matricula\Enums\PermisoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Tablero\Actions\ExportarReporteGeneralExcel;
use App\Modules\Tablero\Actions\GenerarReporteDeAsistencia;
use App\Modules\Tablero\Actions\GenerarReporteDeCargaDocente;
use App\Modules\Tablero\Actions\GenerarReporteDeMatriculados;
use App\Modules\Tablero\Actions\GenerarReporteDeNotas;
use App\Modules\Tablero\Data\FiltrosDeReporte;
use App\Modules\Tablero\Data\ReporteGeneral;
use App\Modules\Tablero\Filament\Pages\ReporteDeAsistencia;
use App\Modules\Tablero\Filament\Pages\ReporteDeCargaDocente;
use App\Modules\Tablero\Filament\Pages\ReporteDeMatriculados;
use App\Modules\Tablero\Filament\Pages\ReporteDeNotas;
use App\Shared\Auth\Models\User;
use Filament\Facades\Filament;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActasFinales;
use Tests\Support\ReportesDelEstudiante;

beforeEach(function (): void {
    Filament::setCurrentPanel('admin');
    $this->escenario = ReportesDelEstudiante::escenario();
    $this->grupo = $this->escenario['curso']['grupo'];
    $this->filtros = new FiltrosDeReporte(periodoId: $this->grupo->periodo_id);
});

/**
 * @return list<list<string|int|float|null>>
 */
function filasDe(ReporteGeneral $reporte, string $seccion): array
{
    return collect($reporte->secciones)->firstOrFail(fn ($candidata): bool => $candidata->titulo === $seccion)->filas;
}

it('lists the enrolled students of the period per group with condition, state and link to UNASAM', function (): void {
    $retirado = ActasFinales::estudiante($this->grupo, 'Bravo', '70000002');
    Matricula::query()->whereKey($retirado->id)->update(['estado' => 'retirada', 'retirado_en' => now()]);

    $reporte = app(GenerarReporteDeMatriculados::class)($this->filtros);

    expect($reporte->titulo)->toBe('Matriculados')
        ->and(filasDe($reporte, 'Resumen por grupo'))->toBe([['ING-1-M', 'Inglés', 'Básico', 1, 1, 1]])
        ->and(filasDe($reporte, 'Detalle de matriculados'))->toHaveCount(2)
        ->and(filasDe($reporte, 'Detalle de matriculados')[1])->toMatchArray([1 => 'ING-1-M', 2 => 'DNI 41234567', 3 => 'Quispe Mamani, Rosa', 5 => 'Regular', 6 => 'Activa']);
});

it('keeps another period, language or group out of the report', function (): void {
    $vacio = app(GenerarReporteDeMatriculados::class)(new FiltrosDeReporte(periodoId: $this->grupo->periodo_id, grupoId: $this->grupo->id + 999));

    expect(filasDe($vacio, 'Detalle de matriculados'))->toBe([]);
});

it('gives the attendance of each student over held and programmed classes, as the attendance contract does', function (): void {
    $reporte = app(GenerarReporteDeAsistencia::class)($this->filtros);
    $contrato = app(AsistenciaDeMatricula::class)($this->escenario['matricula']->id, $this->grupo->id);

    expect(filasDe($reporte, 'Clases por grupo'))->toBe([['ING-1-M', 'Vega Ríos, Carlos', 10, 3, 0, 0, 3]])
        ->and(filasDe($reporte, 'Asistencia por estudiante'))->toBe([['ING-1-M', 'DNI 41234567', 'Quispe Mamani, Rosa', 3, 2, 1, 67, 20]])
        ->and([67, 20])->toBe([$contrato->porcentajeSobreDictadas(), $contrato->porcentajeSobreProgramadas()]);
});

it('summarises the grades of each group with its acta state', function (): void {
    expect(filasDe(app(GenerarReporteDeNotas::class)($this->filtros), 'Notas por grupo'))->toBe([['ING-1-M', 'Inglés', 'Básico', 1, 'Vega Ríos, Carlos', 'Sin acta', 1, null, null, null, null]]);

    ReportesDelEstudiante::cerrarActa($this->escenario['curso']);

    expect(filasDe(app(GenerarReporteDeNotas::class)($this->filtros), 'Notas por grupo'))->toBe([['ING-1-M', 'Inglés', 'Básico', 1, 'Vega Ríos, Carlos', 'Cerrada', 1, 0, 1, 0, 10.8]]);
});

it('gives the teaching load of each teacher with assigned and taught hours', function (): void {
    $reporte = app(GenerarReporteDeCargaDocente::class)($this->filtros);

    expect(filasDe($reporte, 'Carga por docente'))->toBe([['Vega Ríos, Carlos', 1, 20.0, 6.0, 30]])
        ->and(filasDe($reporte, 'Carga por grupo'))->toBe([['Vega Ríos, Carlos', 'ING-1-M', 'Inglés', 'Básico', 'Lunes, Miércoles', 20.0, 6.0, 0.0]]);
});

it('opens each report only with its permission', function (string $pagina, string $permiso): void {
    $sinPermiso = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);
    $conPermiso = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, $permiso]);

    $this->actingAs($sinPermiso)->get($pagina::getUrl())->assertForbidden();
    $this->actingAs($conPermiso)->get($pagina::getUrl())->assertOk()->assertSee('ING-1-M');
})->with([
    'matriculados' => [ReporteDeMatriculados::class, PermisoMatricula::Ver->value],
    'asistencia' => [ReporteDeAsistencia::class, PermisoDocencia::Reportar->value],
    'notas' => [ReporteDeNotas::class, PermisoCertificacion::Actas->value],
    'carga docente' => [ReporteDeCargaDocente::class, PermisoDocencia::Reportar->value],
]);

it('filters the page and downloads the report as a formal PDF and as Excel', function (): void {
    Pdf::fake();
    $usuario = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoMatricula::Ver->value]);

    Livewire::actingAs($usuario)->test(ReporteDeMatriculados::class)
        ->assertSet('data.periodo_id', $this->grupo->periodo_id)
        ->assertSee('Quispe Mamani, Rosa')
        ->set('data.grupo_id', $this->grupo->id + 999)
        ->assertDontSee('Quispe Mamani, Rosa')
        ->set('data.grupo_id', $this->grupo->id)
        ->callAction('descargarPdf')->assertFileDownloaded('matriculados-marzo-2027.pdf')
        ->callAction('descargarExcel')->assertFileDownloaded('matriculados-marzo-2027.xlsx');

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->orientation === 'Landscape'
        && str_contains(view($pdf->viewName, $pdf->viewData)->render(), 'Quispe Mamani, Rosa')
        && str_contains(view($pdf->viewName, $pdf->viewData)->render(), 'Centro de Idiomas y Lenguas Nativas'));
});

it('writes each section as an Excel sheet with numbers as numbers and text as text', function (): void {
    $archivo = app(ExportarReporteGeneralExcel::class)(app(GenerarReporteDeAsistencia::class)($this->filtros))->getFile()->getPathname();
    $libro = IOFactory::load($archivo);
    $hoja = $libro->getSheetByName('Asistencia por estudiante');

    expect($libro->getSheetNames())->toBe(['Clases por grupo', 'Asistencia por estudiante'])
        ->and($hoja?->getCell('C2')->getValue())->toBe('Quispe Mamani, Rosa')
        ->and($hoja?->getCell('C2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($hoja?->getCell('G2')->getValue())->toEqual(67);
});
