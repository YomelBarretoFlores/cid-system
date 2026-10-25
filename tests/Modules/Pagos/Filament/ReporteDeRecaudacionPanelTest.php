<?php

declare(strict_types=1);

use App\Modules\Pagos\Actions\ExportarRecaudacionPdf;
use App\Modules\Pagos\Actions\GenerarReporteDeRecaudacion;
use App\Modules\Pagos\Data\FiltrosDeRecaudacion;
use App\Modules\Pagos\Enums\FuenteConcepto;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Filament\Pages\ReporteDeRecaudacion;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2026-10-24 10:00', 'America/Lima'));
    $this->tesorero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Reportar->value]);
    $this->matricula = Concepto::factory()->create(['codigo' => 'MAT-01', 'nombre' => 'Derecho de matrícula', 'tipo' => TipoConcepto::Matricula, 'fuente' => FuenteConcepto::Tupa]);
    $persona = Persona::factory()->create(['numero_documento' => '41234567', 'apellido_paterno' => '=Quispe', 'apellido_materno' => 'Mamani', 'nombres' => 'Rosa']);
    Pago::factory()->validado()->for($this->matricula)->create(['persona_id' => $persona->id, 'monto' => '80.00', 'fecha_pago' => '2026-10-05', 'numero_operacion' => 'OP-1']);
    Pago::factory()->validado()->for($this->matricula)->create(['persona_id' => $persona->id, 'monto' => '80.50', 'fecha_pago' => '2026-09-30', 'numero_operacion' => 'SETIEMBRE']);
});

it('keeps the report away from users without the report permission', function (): void {
    $cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Validar->value]);

    $this->actingAs($cajero)->get(ReporteDeRecaudacion::getUrl())->assertForbidden();
    $this->actingAs($this->tesorero)->get(ReporteDeRecaudacion::getUrl())->assertOk();
});

it('shows the current month totals by concept and updates with the filters', function (): void {
    $this->actingAs($this->tesorero);

    Livewire::test(ReporteDeRecaudacion::class)
        ->assertSet('data.desde', '2026-10-01 00:00:00')
        ->assertSet('data.hasta', '2026-10-24 00:00:00')
        ->assertSee(['MAT-01', 'Derecho de matrícula', 'TUPA', 'S/ 80.00', '1 operación'])
        ->assertDontSee('SETIEMBRE')
        ->set('data.desde', '2026-09-01')
        ->assertSee(['S/ 160.50', '2 operaciones', 'SETIEMBRE']);
});

it('explains an inverted date range instead of failing', function (): void {
    $this->actingAs($this->tesorero);

    Livewire::test(ReporteDeRecaudacion::class)
        ->set('data.desde', '2026-10-31')
        ->assertSee('La fecha inicial no puede ser posterior a la final.');
});

it('downloads the detail per student as CSV that spreadsheets open safely', function (): void {
    $this->actingAs($this->tesorero);

    $respuesta = Livewire::test(ReporteDeRecaudacion::class)->callAction('descargarCsv')->assertFileDownloaded('recaudacion-2026-10-01-al-2026-10-24.csv');
    $contenido = $respuesta->effects['download']['content'] ?? '';
    $csv = base64_decode($contenido, true) ?: $contenido;

    expect($csv)->toStartWith("\u{FEFF}\"Fecha de pago\",Documento,Estudiante,Concepto,Idioma,Nivel,Periodo,\"Medio de pago\",\"N.° de operación\",\"Monto (S/)\"")
        ->and($csv)->toContain('05/10/2026,"DNI 41234567","\'=Quispe Mamani, Rosa"')
        ->and($csv)->toContain('80.00')
        ->and($csv)->toContain('Total,,,,,,,,,80.00')
        ->and($csv)->not->toContain('SETIEMBRE');
});

it('downloads the consolidated report as PDF for the treasury file', function (): void {
    Pdf::fake();
    $this->actingAs($this->tesorero);

    Livewire::test(ReporteDeRecaudacion::class)->callAction('descargarPdf');

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'recaudacion-2026-10-01-al-2026-10-24.pdf'
        && $pdf->contains(['REPORTE DE RECAUDACIÓN', 'Del 01/10/2026 al 24/10/2026', 'MAT-01', 'Derecho de matrícula', 'S/ 80.00', 'DNI 41234567', 'OP-1']));
});

it('renders the collection report as a real PDF file', function (): void {
    $reporte = app(GenerarReporteDeRecaudacion::class)(new FiltrosDeRecaudacion(CarbonImmutable::parse('2026-10-01'), CarbonImmutable::parse('2026-10-24')));

    expect(app(ExportarRecaudacionPdf::class)($reporte)->generatePdfContent())->toStartWith('%PDF');
});

it('notifies an invalid range instead of downloading', function (string $accion, string $desde, string $mensaje): void {
    $this->actingAs($this->tesorero);

    Livewire::test(ReporteDeRecaudacion::class)
        ->set('data.desde', $desde)
        ->callAction($accion)
        ->assertNotified($mensaje)
        ->assertNoFileDownloaded();
})->with([
    'inverted range as PDF' => ['descargarPdf', '2026-10-31', 'La fecha inicial no puede ser posterior a la final.'],
    'inverted range as CSV' => ['descargarCsv', '2026-10-31', 'La fecha inicial no puede ser posterior a la final.'],
    'malformed date' => ['descargarCsv', 'not-a-date', 'Escribe un rango de fechas válido.'],
]);
