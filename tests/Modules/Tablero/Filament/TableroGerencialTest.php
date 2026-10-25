<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Pagos\Enums\PermisoPago;
use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Tablero\Actions\ArmarTablero;
use App\Modules\Tablero\Actions\ExportarTableroExcel;
use App\Modules\Tablero\Actions\ExportarTableroPdf;
use App\Modules\Tablero\Data\FiltrosDelTablero;
use App\Modules\Tablero\Enums\PermisoTablero;
use App\Modules\Tablero\Filament\Pages\TableroGerencial;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Livewire\Livewire;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\DatosDelTablero;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-20 10:00', 'America/Lima'));
    $this->directora = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoTablero::Ver->value]);
    $this->ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->quechua = Idioma::factory()->create(['nombre' => 'Quechua']);
    DatosDelTablero::periodo('Febrero 2027', '2027-02-01', '2027-02-28', numero: 2);
    $this->marzo = DatosDelTablero::periodo('Marzo 2027', '2027-03-01', '2027-03-31', numero: 3, estado: EstadoPeriodo::EnCurso);
    DatosDelTablero::periodo('Abril 2027', '2027-04-01', '2027-04-30', numero: 4, estado: EstadoPeriodo::MatriculaAbierta);

    $grupo = DatosDelTablero::grupo($this->marzo, $this->ingles, atributos: ['codigo' => 'ING-1-M']);
    DatosDelTablero::horario($grupo, [1, 2, 3, 4, 5], '07:00', '09:00');
    DatosDelTablero::matricula($grupo, DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-02-20'));
    DatosDelTablero::matricula($grupo, DatosDelTablero::pago(TipoConcepto::Matricula, '80.00', '2027-02-21'), retirada: true);
    $this->pendiente = DatosDelTablero::pago(TipoConcepto::Pension, '180.00', '2027-03-18', 'pendiente');
    DatosDelTablero::costo('contratado', '40.00', '2027-01-01');
    DatosDelTablero::clase($grupo, Docente::factory()->create(['condicion_laboral' => CondicionLaboral::Contratado]), '2027-03-02', '07:00', '09:00');
});

it('opens the dashboard only to staff with the dashboard permission', function (): void {
    $cajero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoPago::Ver->value, PermisoPago::Validar->value]);

    $this->actingAs($cajero)->get(TableroGerencial::getUrl())->assertForbidden();
    $this->actingAs($this->directora)->get(TableroGerencial::getUrl())->assertOk()->assertSee('Tablero gerencial');
});

it('opens on the period in progress with the financial and academic indicators', function (): void {
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)
        ->assertSet('data.periodo_id', $this->marzo->id)
        ->assertSee(['Marzo 2027 · Todos los idiomas · Todos los niveles', 'S/ 160.00', '2 pagos validados del periodo'])
        ->assertSee(['Tasa del periodo en todos los idiomas y niveles: 100.0 % (1 de 1 pagos registrados)', 'S/ 180.00 · el más antiguo se registró hoy', 'Mes a mes: sin periodo comparable', 'Año a año: sin periodo comparable'])
        ->assertSee(['Deserción', '50.0 %', 'mayor en Básico', 'Idioma líder', '07:00', 'Lun–Vie'])
        ->assertSee(['ING-1-M', 'S/ 80.00', '2.00 h', '+S/ 80.00'])
        ->assertDontSee(['Reporte de recaudación →', 'Todos los pagos →']);
});

it('updates every indicator with the language and level filters', function (): void {
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)
        ->set('data.idioma_id', $this->quechua->id)
        ->assertSee(['Marzo 2027 · Quechua · Todos los niveles', 'No hay grupos en este periodo.', 'No hay horarios registrados en este periodo.'])
        ->assertDontSee('ING-1-M')
        ->set('data.idioma_id', $this->ingles->id)
        ->set('data.nivel', 'basico')
        ->assertSee(['Marzo 2027 · Inglés · Básico', 'ING-1-M']);
});

it('explains an unknown period instead of failing', function (): void {
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)
        ->set('data.periodo_id', 999999)
        ->assertSee('Elige un periodo válido.');
});

it('links the pending payments and the revenue report only for staff who can see them', function (): void {
    $this->directora->givePermissionTo([PermisoPago::Ver->value, PermisoPago::Reportar->value]);
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)
        ->assertSee(['Reporte de recaudación →', 'Todos los pagos →'])
        ->assertSeeHtml(route('filament.admin.resources.pagos.view', ['record' => $this->pendiente->id]));
});

it('falls back to the latest started period, or the latest one, when no period is in progress', function (): void {
    $this->actingAs($this->directora);
    $this->marzo->update(['estado' => EstadoPeriodo::Cerrado]);

    Livewire::test(TableroGerencial::class)->assertSet('data.periodo_id', $this->marzo->id);

    $this->travelTo(CarbonImmutable::parse('2027-01-15 10:00', 'America/Lima'));

    Livewire::test(TableroGerencial::class)->assertSet('data.periodo_id', Periodo::query()->where('nombre', 'Abril 2027')->value('id'));
});

it('shows the growth against the previous period when there is one to compare with', function (): void {
    DatosDelTablero::matricula(DatosDelTablero::grupo(Periodo::query()->where('nombre', 'Febrero 2027')->sole(), $this->ingles));
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)->assertSee('+100.0 % frente a Febrero 2027');
});

it('explains an unknown language instead of failing', function (): void {
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)
        ->set('data.idioma_id', 999999)
        ->assertSee('Elige un idioma válido.');
});

it('exports the dashboard with the same filters as a PDF', function (): void {
    Pdf::fake();
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)->callAction('descargarPdf');

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === 'tablero-marzo-2027-2027-03-20.pdf'
        && $pdf->contains(['TABLERO DE CONTROL GERENCIAL', 'Marzo 2027 · Todos los idiomas · Todos los niveles', 'S/ 160.00 (2 pagos)', 'ING-1-M', '07:00 – 09:00', 'Lun–Vie', '50.0 %']));
});

it('renders the dashboard as a real PDF file', function (): void {
    $tablero = app(ArmarTablero::class)(new FiltrosDelTablero($this->marzo->id));

    expect(app(ExportarTableroPdf::class)($tablero)->generatePdfContent())->toStartWith('%PDF');
});

it('exports one spreadsheet sheet per indicator, with amounts as numbers and names always as text', function (): void {
    $this->ingles->update(['nombre' => '=HYPERLINK("http://x.test")']);
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)->callAction('descargarExcel')->assertFileDownloaded('tablero-marzo-2027-2027-03-20.xlsx');

    $archivo = app(ExportarTableroExcel::class)(app(ArmarTablero::class)(new FiltrosDelTablero($this->marzo->id)))->getFile()->getPathname();
    $libro = IOFactory::load($archivo);
    $recaudacion = $libro->getSheetByName('Recaudación');

    expect($libro->getSheetNames())->toBe(['Resumen', 'Recaudación', 'Por validar', 'Rentabilidad', 'Matrículas', 'Deserción', 'Demanda horaria'])
        ->and($recaudacion?->getCell('A2')->getDataType())->toBe(DataType::TYPE_STRING)
        ->and($recaudacion?->getCell('A2')->getValue())->toBe('=HYPERLINK("http://x.test")')
        ->and($recaudacion?->getCell('D2')->getValue())->toEqual(160)
        ->and($libro->getSheetByName('Rentabilidad')?->getCell('H2')->getValue())->toEqual(80)
        ->and($libro->getSheetByName('Resumen')?->getCell('B2')->getValue())->toBe('Marzo 2027 · Todos los idiomas · Todos los niveles');
});

it('explains an unknown level instead of showing every level', function (): void {
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)
        ->set('data.nivel', 'experto')
        ->assertSee('Elige un nivel válido.')
        ->assertDontSee('Marzo 2027 · Todos los idiomas · Todos los niveles');
});

it('keeps the pending-validation figures for every language and level when the page is filtered', function (): void {
    $this->actingAs($this->directora);

    Livewire::test(TableroGerencial::class)
        ->set('data.idioma_id', $this->quechua->id)
        ->assertSee('Tasa del periodo en todos los idiomas y niveles: 100.0 % (1 de 1 pagos registrados)');
});
