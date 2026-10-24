<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Docencia\Actions\ConsolidarHorasDictadas;
use App\Modules\Docencia\Actions\ExportarExpedienteDeHorasPdf;
use App\Modules\Docencia\Enums\CondicionLaboral;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Pages\ExpedienteDeHorasDocentes;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\CostoPorHora;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-20 12:00', 'America/Lima'));
    $this->tesorero = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Reportar->value]);
    $grupo = Grupo::factory()->create(['ciclo_id' => Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->create(['nivel' => NivelCurricular::Basico])->id]);
    $this->rosa = Docente::factory()->create(['persona_id' => Persona::factory()->create(['apellido_paterno' => 'Rímac', 'apellido_materno' => 'Huaylas', 'nombres' => 'Rosa'])->id, 'condicion_laboral' => CondicionLaboral::Contratado]);
    $this->luis = Docente::factory()->create(['persona_id' => Persona::factory()->create(['apellido_paterno' => '=Alva', 'apellido_materno' => 'Paz', 'nombres' => 'Luis'])->id, 'condicion_laboral' => CondicionLaboral::Tercero]);
    CostoPorHora::query()->create(['condicion_laboral' => 'contratado', 'costo_hora' => '1250.00', 'vigente_desde' => '2027-01-01', 'registrado_por' => $this->tesorero->id]);
    foreach ([[$this->rosa, '2027-03-01', '18:00', '20:00'], [$this->rosa, '2027-03-03', '18:00', '19:30'], [$this->luis, '2027-03-02', '08:00', '10:00'], [$this->rosa, '2027-02-26', '18:00', '20:00']] as [$docente, $fecha, $inicio, $fin]) {
        ClaseDictada::query()->create(['grupo_id' => $grupo->id, 'fecha' => $fecha, 'hora_inicio' => $inicio, 'hora_fin' => $fin, 'docente_id' => $docente->id, 'registrado_por' => $this->tesorero->id]);
    }
});

it('keeps the hours report away from users without the report permission', function (): void {
    $gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value, PermisoDocencia::Gestionar->value, PermisoDocencia::Costos->value]);

    $this->actingAs($gestor)->get(ExpedienteDeHorasDocentes::getUrl())->assertForbidden();
});

it('shows the teachers with hours in the current month with their totals and warns about hours without cost', function (): void {
    $this->actingAs($this->tesorero)
        ->get(ExpedienteDeHorasDocentes::getUrl())
        ->assertOk()
        ->assertSeeInOrder(['Marzo 2027', '=Alva Paz, Luis', 'Tercero', '2.00 h', 'Sin costo vigente', 'Rímac Huaylas, Rosa', 'Contratado', '3.50 h', 'S/ 4,375.00'])
        ->assertSee('Las horas sin costo vigente no suman al monto; registra el costo por hora de su categoría.');
});

it('changes the month from the filter', function (): void {
    $this->actingAs($this->tesorero);

    Livewire::test(ExpedienteDeHorasDocentes::class)
        ->fillForm(['anio' => 2027, 'mes' => 2])
        ->assertSee('Febrero 2027')
        ->assertSee('Rímac Huaylas, Rosa')
        ->assertDontSee('=Alva Paz, Luis');
});

it('downloads the expediente of one teacher as a PDF for the DGF', function (): void {
    Pdf::fake();
    $this->actingAs($this->tesorero);

    Livewire::test(ExpedienteDeHorasDocentes::class)
        ->callAction(TestAction::make('descargarPdf')->arguments(['docente' => $this->rosa->id]));

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->downloadName === "expediente-de-horas-2027-03-{$this->rosa->codigo}.pdf");
});

it('puts every required field of the expediente in the PDF', function (): void {
    $expediente = app(ConsolidarHorasDictadas::class)(2027, 3, $this->rosa->id)[0];
    $html = view('docencia::pdf.expediente-de-horas', ['expediente' => $expediente])->render();

    foreach (['EXPEDIENTE DE HORAS DICTADAS', 'Periodo: Marzo 2027', $this->rosa->codigo, 'Rímac Huaylas, Rosa', 'Contratado', '<td>Inglés</td><td>Básico</td><td class="monto">3.50</td><td class="monto">S/ 1,250.00</td><td class="monto">S/ 4,375.00</td>', 'Monto bruto referencial', 'No incluye retenciones de ley'] as $texto) {
        expect($html)->toContain($texto);
    }

    expect(app(ExportarExpedienteDeHorasPdf::class)($expediente)->generatePdfContent())->toStartWith('%PDF');
});

it('downloads every line of the month as a CSV that spreadsheets open safely', function (): void {
    $this->actingAs($this->tesorero);

    $respuesta = Livewire::test(ExpedienteDeHorasDocentes::class)->callAction('descargarCsv')->assertFileDownloaded('expediente-de-horas-2027-03.csv');
    $contenido = $respuesta->effects['download']['content'] ?? '';
    $csv = base64_decode($contenido, true) ?: $contenido;

    expect($csv)->toStartWith("\u{FEFF}Periodo,Código,Docente,Categoría,Idioma,Nivel,\"Total de horas\",\"Costo por hora (S/)\",\"Monto bruto referencial (S/)\"")
        ->and($csv)->toContain("\"Marzo 2027\",{$this->luis->codigo},\"'=Alva Paz, Luis\",Tercero,Inglés,Básico,2.00,\"Sin costo vigente\",")
        ->and($csv)->toContain("\"Marzo 2027\",{$this->rosa->codigo},\"Rímac Huaylas, Rosa\",Contratado,Inglés,Básico,3.50,1250.00,4375.00")
        ->and($csv)->toContain('Total,,,,,,5.50,,4375.00')
        ->and($csv)->not->toContain('Febrero');
});

it('refuses the downloads without the report permission even with the page open', function (): void {
    $this->actingAs($this->tesorero);
    $pagina = Livewire::test(ExpedienteDeHorasDocentes::class);
    $this->tesorero->revokePermissionTo(PermisoDocencia::Reportar->value);

    $pagina->assertActionHidden('descargarCsv')
        ->assertActionHidden(TestAction::make('descargarPdf')->arguments(['docente' => $this->rosa->id]))
        ->assertNoFileDownloaded();
});

it('notifies an invalid month instead of downloading', function (): void {
    $this->actingAs($this->tesorero);

    Livewire::test(ExpedienteDeHorasDocentes::class)
        ->set('data.mes', '13')
        ->callAction('descargarCsv')
        ->assertNotified('Elige un mes y un año válidos.')
        ->assertNoFileDownloaded();
});

it('notifies instead of downloading the PDF of a teacher without hours in the month', function (mixed $docente): void {
    $this->actingAs($this->tesorero);

    Livewire::test(ExpedienteDeHorasDocentes::class)
        ->callAction(TestAction::make('descargarPdf')->arguments(['docente' => $docente === 'sin horas' ? Docente::factory()->create()->id : $docente]))
        ->assertNotified('El docente no tiene horas dictadas en este mes.')
        ->assertNoFileDownloaded();
})->with(['sin horas', null, 'abc']);
