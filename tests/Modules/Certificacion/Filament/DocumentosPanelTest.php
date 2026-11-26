<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Certificacion\Actions\EmitirConstanciaDeNotas;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Enums\TipoDocumento;
use App\Modules\Certificacion\Filament\Resources\Actas\Pages\ViewActa;
use App\Modules\Certificacion\Filament\Resources\Documentos\DocumentoResource;
use App\Modules\Certificacion\Filament\Resources\Documentos\Pages\ListDocumentos;
use App\Modules\Certificacion\Filament\Resources\Documentos\Pages\ViewDocumento;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\Documento;
use App\Modules\Certificacion\Models\Rectificacion;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Tests\Support\ActasFinales;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Documentos->value, PermisoCertificacion::Actas->value]);
    $this->ana = Persona::factory()->create(['apellido_paterno' => 'Alva', 'apellido_materno' => 'Prueba', 'nombres' => 'Ana', 'numero_documento' => '70000001']);
    ActasFinales::historial($this->ana, 'Inglés', [[1, '14.00', 'aprobado'], [2, '15.50', 'aprobado'], [3, '16.25', 'aprobado'], [4, '13.00', 'aprobado']]);
});

it('keeps the documents away from panel users without the documents permission', function (): void {
    $documento = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->gestor);
    $ajeno = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoCertificacion::Actas->value]);

    $this->actingAs($ajeno)->get(DocumentoResource::getUrl('index'))->assertForbidden();
    $this->actingAs($ajeno)->get(DocumentoResource::getUrl('view', ['record' => $documento]))->assertForbidden();
    $this->actingAs($ajeno)->get(route('panel.documentos.pdf', $documento))->assertForbidden();
    Livewire::actingAs($ajeno)->test(ViewActa::class, ['record' => Acta::query()->where('ciclo_numero', 4)->sole()->getRouteKey()])->assertActionHidden('emitirConstancias');
});

it('issues each type of document from the panel and opens it', function (Closure $datos, TipoDocumento $tipo): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListDocumentos::class)
        ->callAction('emitir', ['persona_id' => $this->ana->id, ...$datos->call($this)])
        ->assertHasNoActionErrors()
        ->assertNotified('Documento emitido')
        ->assertRedirect(DocumentoResource::getUrl('view', ['record' => Documento::query()->sole()]));

    expect(Documento::query()->sole())->tipo->toBe($tipo)->persona_id->toBe($this->ana->id);
})->with([
    'a grade record' => [fn (): array => ['tipo' => 'constancia_notas', 'idioma' => 'Inglés', 'nivel' => 'basico'], TipoDocumento::ConstanciaNotas],
    'a level certificate' => [fn (): array => ['tipo' => 'certificado_nivel', 'idioma' => 'Inglés', 'nivel' => 'basico'], TipoDocumento::CertificadoNivel],
    'an enrollment record' => [fn (): array => ['tipo' => 'constancia_matricula', 'matricula_id' => Matricula::factory()->activa()->create(['persona_id' => $this->ana->id, 'grupo_id' => Grupo::factory()->create()->id])->id], TipoDocumento::ConstanciaMatricula],
]);

it('shows the rule when a document cannot be issued and issues nothing', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ListDocumentos::class)
        ->callAction('emitir', ['tipo' => 'certificado_nivel', 'persona_id' => $this->ana->id, 'idioma' => 'Inglés', 'nivel' => 'intermedio'])
        ->assertNotified('La persona aún no aprueba todos los ciclos del nivel Intermedio de Inglés en actas cerradas.');

    expect(Documento::query()->count())->toBe(0);
});

it('issues the certificates of a period in bulk and reports how many', function (): void {
    $this->actingAs($this->gestor);
    $periodo = Acta::query()->where('ciclo_numero', 4)->sole()->periodo_id;

    Livewire::test(ListDocumentos::class)
        ->callAction('emitirCertificadosDelPeriodo', ['periodo_id' => $periodo])
        ->assertNotified('1 certificado emitido');
    Livewire::test(ListDocumentos::class)
        ->callAction('emitirCertificadosDelPeriodo', ['periodo_id' => $periodo])
        ->assertNotified('0 certificados emitidos');

    expect(Documento::query()->sole()->nivel)->toBe(NivelCurricular::Basico);
});

it('issues the grade records of a closed acta from its page', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(ViewActa::class, ['record' => Acta::query()->where('ciclo_numero', 4)->sole()->getRouteKey()])
        ->callAction('emitirConstancias')
        ->assertNotified('1 constancia emitida');

    expect(Documento::query()->sole()->tipo)->toBe(TipoDocumento::ConstanciaNotas);
});

it('lists and filters documents by type and state', function (): void {
    $notas = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->gestor);
    $anulada = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->gestor);
    $anulada->update(['anulado_por' => $this->gestor->id, 'anulado_en' => now(), 'motivo_anulacion' => 'Se emitió dos veces.']);
    $this->actingAs($this->gestor);

    Livewire::test(ListDocumentos::class)
        ->assertCanSeeTableRecords([$notas, $anulada])
        ->assertTableColumnStateSet('estado', 'Vigente', $notas)
        ->assertTableColumnStateSet('estado', 'Anulado', $anulada)
        ->filterTable('estado', 'vigente')
        ->assertCanSeeTableRecords([$notas])
        ->assertCanNotSeeTableRecords([$anulada])
        ->resetTableFilters()
        ->filterTable('tipo', 'certificado_nivel')
        ->assertCanNotSeeTableRecords([$notas, $anulada]);
});

it('voids a document with a reason, after which its PDF is no longer offered or served', function (): void {
    Pdf::fake();
    $documento = app(EmitirConstanciaDeNotas::class)($this->ana->id, 'Inglés', NivelCurricular::Basico, $this->gestor);
    $this->actingAs($this->gestor)->get(route('panel.documentos.pdf', $documento))->assertOk();

    Livewire::test(ViewDocumento::class, ['record' => $documento->getRouteKey()])
        ->callAction('anular', ['motivo' => 'Mal'])
        ->assertHasActionErrors(['motivo' => 'min']);
    Livewire::test(ViewDocumento::class, ['record' => $documento->getRouteKey()])
        ->callAction('anular', ['motivo' => 'Se entregó a otra persona por error.'])
        ->assertHasNoActionErrors()
        ->assertNotified('Documento anulado')
        ->assertActionHidden('anular')
        ->assertActionHidden('descargarPdf');

    expect($documento->refresh()->estaVigente())->toBeFalse();
    $this->get(route('panel.documentos.pdf', $documento))->assertNotFound();
    $this->get(DocumentoResource::getUrl('view', ['record' => $documento]))->assertSeeInOrder(['Anulado', 'Se entregó a otra persona por error.']);
});

it('shows the detail of a level certificate with its cycles', function (): void {
    $this->actingAs($this->gestor);
    Livewire::test(ListDocumentos::class)->callAction('emitir', ['tipo' => 'certificado_nivel', 'persona_id' => $this->ana->id, 'idioma' => 'Inglés', 'nivel' => 'basico']);

    $this->get(DocumentoResource::getUrl('view', ['record' => Documento::query()->sole()]))
        ->assertOk()
        ->assertSeeInOrder(['Certificado de culminación de nivel', 'Alva Prueba, Ana', 'DNI 70000001', 'Vigente', 'Inglés', 'Básico', '240', '14.69', 'Básico 1 (1)', '14.00', 'Básico 4 (4)', '13.00']);
});

it('shows the detail of an enrollment record and hides the bulk grade records of an acta under rectification', function (): void {
    $this->actingAs($this->gestor);
    $matricula = Matricula::factory()->activa()->create(['persona_id' => $this->ana->id, 'grupo_id' => Grupo::factory()->create(['codigo' => 'POR-2-T'])->id]);
    Livewire::test(ListDocumentos::class)->callAction('emitir', ['tipo' => 'constancia_matricula', 'persona_id' => $this->ana->id, 'matricula_id' => $matricula->id]);
    $acta = Acta::query()->where('ciclo_numero', 4)->sole();
    Rectificacion::query()->create(['acta_id' => $acta->id, 'motivo' => 'Corrección pendiente.', 'autorizada_por' => $this->gestor->id, 'autorizada_en' => now()]);
    $acta->update(['estado' => 'abierta']);

    $this->get(DocumentoResource::getUrl('view', ['record' => Documento::query()->sole()]))
        ->assertOk()
        ->assertSeeInOrder(['Constancia de matrícula', 'Grupo', 'POR-2-T', 'Periodo', 'Modalidad', 'Horario']);
    Livewire::test(ViewActa::class, ['record' => $acta->getRouteKey()])->assertActionHidden('emitirConstancias');
});
