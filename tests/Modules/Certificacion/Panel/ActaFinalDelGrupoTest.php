<?php

declare(strict_types=1);

use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Enums\PermisoCertificacion;
use App\Modules\Certificacion\Filament\Docente\Pages\ActaFinalDelGrupo;
use App\Modules\Certificacion\Filament\Docente\Pages\MisActasFinales;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    Filament::setCurrentPanel('docente');
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
    $this->usuario = $this->curso['usuario'];
    $this->ana = ActasFinales::estudiante($this->grupo, 'Alva', '70000001');
    $this->beto = ActasFinales::estudiante($this->grupo, 'Bravo', '70000002');
    ActasFinales::preActa($this->ana, [$this->curso['parcial']->id => '15.00', $this->curso['final']->id => '12.00', $this->curso['participacion']->id => '18.00']);
    ActasFinales::preActa($this->beto, [$this->curso['parcial']->id => '8.00', $this->curso['final']->id => '10.00']);
});

function cerrarActaDelCurso(): Acta
{
    app(CerrarActa::class)(test()->grupo->id, test()->curso['docente']->id, test()->usuario);

    return Acta::query()->sole();
}

it('links the acta from the list of groups, the group tabs and the menu', function (): void {
    $this->actingAs($this->usuario)->get(route('filament.docente.pages.mis-grupos'))
        ->assertSee(route('filament.docente.pages.grupos.acta', $this->grupo->id))
        ->assertSee(route('filament.docente.pages.actas'));
    $this->actingAs($this->usuario)->get(route('filament.docente.pages.grupos.notas', $this->grupo->id))->assertSee(route('filament.docente.pages.grupos.acta', $this->grupo->id));
});

it('previews the acta from the pre-acta with the course, teacher, averages and final states, and the grade calendar', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.acta', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder([
            'Acta final del grupo ING-1-M', 'Vista previa', 'Fechas para validar: del 29 mar 2027 al 5 abr 2027.',
            'Inglés', 'Básico · Básico I', 'Marzo 2027', 'Vega Ríos, Carlos', 'ID '.$this->curso['docente']->id,
            'Alva Prueba, Ana', 'DNI 70000001', '15', '12', '18', '14.40', 'Aprobado',
            'Bravo Prueba, Ana', 'DNI 70000002', '8', '10', '—', '7.20', 'Desaprobado',
        ])
        ->assertSee('Validar y cerrar acta')
        ->assertDontSee('Descargar acta (PDF)');

    expect(Acta::query()->count())->toBe(0);
});

it('shows the stages of the acta, from the grades still missing to the closed acta', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.acta', $this->grupo->id))
        ->assertSeeInOrder(['Registra las notas', '5 de 6 notas', 'Revisa el acta', 'Cuando completes las notas', 'Valida y cierra', 'Hasta el 5 abr', 'Vista previa']);

    cerrarActaDelCurso();

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.acta', $this->grupo->id))
        ->assertSeeInOrder(['Registra las notas', '5 de 6 notas', 'Revisa el acta', 'Revisada', 'Valida y cierra', 'Cerrada', 'Validada el 30 mar 2027.']);
});

it('closes the acta when the teacher validates it and then shows it read-only with its PDF', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(ActaFinalDelGrupo::class, ['grupo' => $this->grupo->id])
        ->call('validar')
        ->assertHasNoErrors()
        ->assertSee('Acta final validada y cerrada.')
        ->assertSee('Validada el 30 mar 2027.')
        ->assertSee(route('docente.acta.pdf', $this->grupo->id))
        ->assertDontSee('Validar y cerrar acta');

    expect(Acta::query()->sole()->estado)->toBe(EstadoActa::Cerrada);
});

it('downloads the PDF with a full request instead of swapping the panel page in place', function (): void {
    cerrarActaDelCurso();
    $html = (string) $this->actingAs($this->usuario)->get(route('filament.docente.pages.grupos.acta', $this->grupo->id))->getContent();

    expect(preg_match('/<a\\s[^>]*href="'.preg_quote(route('docente.acta.pdf', $this->grupo->id), '/').'"[^>]*>/s', $html, $enlace))->toBe(1)
        ->and($enlace[0])->not->toContain('wire:navigate');
});

it('closes the acta once the teacher confirms the header action', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(ActaFinalDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertActionEnabled('validarActa')
        ->callAction('validarActa')
        ->assertHasNoErrors()
        ->assertSee('Acta final validada y cerrada.')
        ->assertActionHidden('validarActa')
        ->assertActionVisible('descargarPdf');

    expect(Acta::query()->sole()->estado)->toBe(EstadoActa::Cerrada);
});

it('disables the header action outside the grade calendar and closes nothing', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-04-06 08:00', 'America/Lima'));

    Livewire::actingAs($this->usuario)
        ->test(ActaFinalDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertActionDisabled('validarActa')
        ->callAction('validarActa');

    expect(Acta::query()->count())->toBe(0);
});

it('shows and logs a rejected validation outside the grade calendar and stores nothing', function (): void {
    Log::spy();
    $this->travelTo(CarbonImmutable::parse('2027-04-06 08:00', 'America/Lima'));

    Livewire::actingAs($this->usuario)
        ->test(ActaFinalDelGrupo::class, ['grupo' => $this->grupo->id])
        ->call('validar')
        ->assertHasErrors('acta')
        ->assertSee('El acta final del grupo ING-1-M se valida del 29 mar 2027 al 5 abr 2027.');

    expect(Acta::query()->count())->toBe(0);
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', ['regla' => 'El acta final del grupo ING-1-M se valida del 29 mar 2027 al 5 abr 2027.'])->once();
});

it('lets the teacher rectify and close again once a rectification is authorized', function (): void {
    $acta = cerrarActaDelCurso();
    app(AutorizarRectificacion::class)($acta->id, 'Error al transcribir el examen final.', User::factory()->create()->givePermissionTo(PermisoCertificacion::Actas->value));

    Livewire::actingAs($this->usuario)
        ->test(ActaFinalDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSee('En rectificación')
        ->assertSee('Motivo autorizado: Error al transcribir el examen final.')
        ->assertSet("notas.{$this->beto->id}.{$this->curso['final']->id}", '10.00')
        ->assertSet("notas.{$this->beto->id}.{$this->curso['participacion']->id}", '')
        ->set("notas.{$this->beto->id}.{$this->curso['final']->id}", '18')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Notas del acta rectificadas. Valídala para cerrarla de nuevo.')
        ->call('validar')
        ->assertSee('Acta final validada y cerrada.');

    expect($acta->refresh()->estado)->toBe(EstadoActa::Cerrada)
        ->and(NotaFinal::query()->where('matricula_id', $this->beto->id)->sole())
        ->promedio->toBe('10.40')
        ->estado_final->toBe(EstadoFinal::Desaprobado);
});

it('tells the teacher when the group has no evaluation scheme', function (): void {
    EsquemaEvaluacion::query()->update(['vigente_desde' => '2028-01-01']);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.acta', $this->grupo->id))
        ->assertOk()
        ->assertSee('El grupo ING-1-M no tiene un sistema de evaluación vigente; pide a coordinación que lo registre.')
        ->assertDontSee('Validar y cerrar acta');
});

it('hides the acta and its PDF from teachers not in charge of the group and from users without a teacher profile', function (): void {
    cerrarActaDelCurso();
    [$otro] = PortalDocente::docente();

    $this->actingAs($otro)->get(route('filament.docente.pages.grupos.acta', $this->grupo->id))->assertNotFound();
    $this->actingAs($otro)->get(route('docente.acta.pdf', $this->grupo->id))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('filament.docente.pages.grupos.acta', $this->grupo->id))->assertForbidden();
});

it('stops the open page once the group moves to another teacher', function (): void {
    $pagina = Livewire::actingAs($this->usuario)->test(ActaFinalDelGrupo::class, ['grupo' => $this->grupo->id]);
    PortalDocente::asignar($this->grupo, Docente::factory()->create());

    $pagina->call('validar')->assertNotFound();

    expect(Acta::query()->count())->toBe(0);
});

it('downloads the PDF of a closed acta only', function (): void {
    Pdf::fake();
    $this->actingAs($this->usuario)->get(route('docente.acta.pdf', $this->grupo->id))->assertNotFound();
    $acta = cerrarActaDelCurso();

    $this->actingAs($this->usuario)->get(route('docente.acta.pdf', $this->grupo->id))->assertOk();

    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->viewName === 'certificacion::pdf.acta-final'
        && $pdf->downloadName === 'acta-final-ING-1-M.pdf'
        && $pdf->viewData['codigo'] === sprintf('ACTA-%06d', $acta->id)
        && $pdf->viewData['acta']->filas[0]->promedio === '14.40');

    app(AutorizarRectificacion::class)($acta->id, 'Error al transcribir el examen final.', User::factory()->create()->givePermissionTo(PermisoCertificacion::Actas->value));
    $this->actingAs($this->usuario)->get(route('docente.acta.pdf', $this->grupo->id))->assertNotFound();
});

it('renders the acta PDF with the course, teacher and grades', function (): void {
    $acta = cerrarActaDelCurso();

    $html = view('certificacion::pdf.acta-final', ['acta' => ActaDelGrupo::desde($acta->load('notas')), 'codigo' => 'ACTA-000001'])->render();

    expect($html)->toContain('ACTA FINAL DE EVALUACIÓN')
        ->toContain('Vega Ríos, Carlos')
        ->toContain('Examen parcial (40 %)')
        ->toContain('Alva Prueba, Ana')
        ->toContain('<strong>14.40</strong>')
        ->toContain('Desaprobado')
        ->toContain('data:image/png;base64,');
});

it('lists the actas the teacher closed with their state', function (): void {
    $acta = cerrarActaDelCurso();

    Livewire::actingAs($this->usuario)
        ->test(MisActasFinales::class)
        ->assertSeeInOrder(['Grupo', 'Periodo', 'Estado', 'Validada', 'ING-1-M', 'Inglés Básico I · 1 de 2 aprobados', 'Marzo 2027', 'Cerrada', '30 mar 2027', 'Abrir', 'PDF'])
        ->assertSee(route('filament.docente.pages.grupos.acta', $this->grupo->id))
        ->assertSee(route('docente.acta.pdf', $this->grupo->id));
    $this->actingAs($this->usuario)->get(route('filament.docente.pages.actas'))->assertOk()->assertSee(['ING-1-M', 'Inglés Básico I · 1 de 2 aprobados']);

    [$otro] = PortalDocente::docente();
    Livewire::actingAs($otro)->test(MisActasFinales::class)
        ->assertSee('Aún no has cerrado actas finales.')
        ->assertDontSee('ING-1-M');
});

it('shows and logs a rejected rectified grade and keeps the acta as it was', function (): void {
    Log::spy();
    $acta = cerrarActaDelCurso();
    app(AutorizarRectificacion::class)($acta->id, 'Error al transcribir el examen final.', User::factory()->create()->givePermissionTo(PermisoCertificacion::Actas->value));

    Livewire::actingAs($this->usuario)
        ->test(ActaFinalDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set("notas.{$this->beto->id}.{$this->curso['final']->id}", '25')
        ->call('guardar')
        ->assertHasErrors('acta')
        ->assertSee('La nota 25 no es válida: usa un número de 0 a 20 con hasta dos decimales.');

    expect(NotaFinal::query()->where('matricula_id', $this->beto->id)->sole()->promedio)->toBe('7.20');
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', ['regla' => 'La nota 25 no es válida: usa un número de 0 a 20 con hasta dos decimales.'])->once();
});

it('marks the menu entry of the page the teacher is on', function (string $pagina, string $entrada): void {
    $html = $this->actingAs($this->usuario)->get($pagina === 'actas' ? route('filament.docente.pages.actas') : route('filament.docente.pages.grupos.acta', $this->grupo->id))->getContent();
    $enlace = fn (string $ruta): bool => preg_match('/<a\s+href="'.preg_quote(route($ruta), '/').'"[^>]*aria-current="page"/s', (string) $html) === 1;

    expect($enlace($entrada))->toBeTrue()
        ->and($enlace($entrada === 'filament.docente.pages.actas' ? 'filament.docente.pages.mis-grupos' : 'filament.docente.pages.actas'))->toBeFalse();
})->with([
    'the actas list' => ['actas', 'filament.docente.pages.actas'],
    'the acta of a group' => ['acta', 'filament.docente.pages.mis-grupos'],
]);

it('lists an acta under rectification with its state', function (): void {
    $acta = cerrarActaDelCurso();
    app(AutorizarRectificacion::class)($acta->id, 'Error al transcribir el examen final.', User::factory()->create()->givePermissionTo(PermisoCertificacion::Actas->value));

    Livewire::actingAs($this->usuario)->test(MisActasFinales::class)->assertSeeInOrder(['ING-1-M', 'En rectificación']);
});

it('lists an open acta without validation date with a dash', function (): void {
    $acta = cerrarActaDelCurso();
    app(AutorizarRectificacion::class)($acta->id, 'Error al transcribir el examen final.', User::factory()->create()->givePermissionTo(PermisoCertificacion::Actas->value));
    DB::table('cer_actas')->where('id', $acta->id)->update(['validada_en' => null, 'validada_por' => null]);

    Livewire::actingAs($this->usuario)->test(MisActasFinales::class)->assertOk()->assertSeeInOrder(['ING-1-M', 'En rectificación', '—']);
});

it('shows the validation day in Lima time, also late at night', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 23:30', 'America/Lima'));
    cerrarActaDelCurso();

    $this->actingAs($this->usuario)->get(route('filament.docente.pages.grupos.acta', $this->grupo->id))->assertSee('Validada el 30 mar 2027.');
    Livewire::actingAs($this->usuario)->test(MisActasFinales::class)->assertSee('30 mar 2027')->assertDontSee('31 mar 2027');
});
