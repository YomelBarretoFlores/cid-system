<?php

declare(strict_types=1);

use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Certificacion\Actions\AutorizarRectificacion;
use App\Modules\Certificacion\Actions\CerrarActa;
use App\Modules\Certificacion\Data\ActaDelGrupo;
use App\Modules\Certificacion\Enums\EstadoActa;
use App\Modules\Certificacion\Enums\EstadoFinal;
use App\Modules\Certificacion\Livewire\ActaFinalDelGrupo;
use App\Modules\Certificacion\Livewire\MisActasFinales;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Certificacion\Models\NotaFinal;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
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
    $this->actingAs($this->usuario)->get(route('docente.grupos'))
        ->assertSee(route('docente.acta', $this->grupo->id))
        ->assertSee(route('docente.actas'));
    $this->actingAs($this->usuario)->get(route('docente.notas', $this->grupo->id))->assertSee(route('docente.acta', $this->grupo->id));
});

it('previews the acta from the pre-acta with the course, teacher, averages and final states, and the grade calendar', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('docente.acta', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder([
            'Acta final del grupo ING-1-M', 'Vista previa', 'Fechas para validar: del 29 mar 2027 al 5 abr 2027.',
            'Inglés', 'Básico · Básico I', 'Marzo 2027', 'Vega Ríos, Carlos', 'ID '.$this->curso['docente']->id,
            'Alva Prueba, Ana', 'DNI 70000001', '15', '12', '18', '14.40', 'Aprobado',
            'Bravo Prueba, Ana', 'DNI 70000002', '8', '10', '—', '7.20', 'Desaprobado',
            'Validar y cerrar acta',
        ])
        ->assertDontSee('Descargar acta (PDF)');

    expect(Acta::query()->count())->toBe(0);
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
    app(AutorizarRectificacion::class)($acta->id, 'Error al transcribir el examen final.', User::factory()->create());

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
        ->get(route('docente.acta', $this->grupo->id))
        ->assertOk()
        ->assertSee('El grupo ING-1-M no tiene un sistema de evaluación vigente; pide a coordinación que lo registre.')
        ->assertDontSee('Validar y cerrar acta');
});

it('hides the acta and its PDF from teachers not in charge of the group and from users without a teacher profile', function (): void {
    cerrarActaDelCurso();
    [$otro] = PortalDocente::docente();

    $this->actingAs($otro)->get(route('docente.acta', $this->grupo->id))->assertNotFound();
    $this->actingAs($otro)->get(route('docente.acta.pdf', $this->grupo->id))->assertNotFound();
    $this->actingAs(User::factory()->create())->get(route('docente.acta', $this->grupo->id))->assertForbidden();
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

    app(AutorizarRectificacion::class)($acta->id, 'Error al transcribir el examen final.', User::factory()->create());
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
        ->assertSeeInOrder(['ING-1-M · Inglés Básico I', 'Marzo 2027 · 1 de 2 aprobados · validada el 30 mar 2027', 'Cerrada'])
        ->assertSee(route('docente.acta', $this->grupo->id));

    [$otro] = PortalDocente::docente();
    Livewire::actingAs($otro)->test(MisActasFinales::class)
        ->assertSee('Aún no has cerrado actas finales.')
        ->assertDontSee('ING-1-M');
});
