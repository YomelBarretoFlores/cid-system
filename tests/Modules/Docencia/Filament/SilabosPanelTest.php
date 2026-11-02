<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Pages\Silabos;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Filament\Actions\Testing\TestAction;
use Filament\Facades\Filament;
use Illuminate\Testing\Constraints\SeeInOrder;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Spatie\LaravelPdf\Facades\Pdf;
use Spatie\LaravelPdf\PdfBuilder;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;
use Tests\Support\Silabos as EscenarioDeSilabos;

// Marzo 2027: ING-1-M published by Vega Ríos, ING-1-B a draft of Paz Soto, FRA-1-M without syllabus nor teacher, ING-1-X cancelled.
// Abril 2027 holds ING-2-A, without syllabus.
beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 09:00', 'America/Lima'));
    $curso = ActasFinales::curso();
    $this->publicado = $curso['grupo'];
    EscenarioDeSilabos::publicar($this->publicado, $curso['docente'], $curso['usuario']);

    [, $this->otroDocente] = PortalDocente::docente();
    Persona::query()->whereKey($this->otroDocente->persona_id)->update(['apellido_paterno' => 'Paz', 'apellido_materno' => 'Soto', 'nombres' => 'Lucía']);
    $this->borrador = Grupo::factory()->create(['codigo' => 'ING-1-B', 'ciclo_id' => $this->publicado->ciclo_id, 'periodo_id' => $this->publicado->periodo_id, 'estado' => EstadoGrupo::Abierto]);
    PortalDocente::asignar($this->borrador, $this->otroDocente);
    EscenarioDeSilabos::borrador($this->borrador, $this->otroDocente);

    $this->frances = Idioma::factory()->create(['nombre' => 'Francés']);
    $this->falta = Grupo::factory()->create(['codigo' => 'FRA-1-M', 'ciclo_id' => Ciclo::factory()->for($this->frances)->create(['numero' => 1])->id, 'periodo_id' => $this->publicado->periodo_id]);
    Grupo::factory()->create(['codigo' => 'ING-1-X', 'ciclo_id' => $this->publicado->ciclo_id, 'periodo_id' => $this->publicado->periodo_id, 'estado' => EstadoGrupo::Cancelado]);
    $this->abril = Periodo::factory()->create(['nombre' => 'Abril 2027', 'fecha_inicio' => '2027-04-01', 'fecha_fin' => '2027-04-30']);
    Grupo::factory()->create(['codigo' => 'ING-2-A', 'ciclo_id' => $this->publicado->ciclo_id, 'periodo_id' => $this->abril->id]);

    $this->coordinador = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value]);
});

it('keeps the syllabi away from staff without the teaching view permission', function (): void {
    $sinPermiso = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Reportar->value]);

    $this->actingAs($sinPermiso)->get(Silabos::getUrl())->assertForbidden();
});

it('lists the groups of the current period with their teacher, syllabus state and publication date', function (): void {
    $this->actingAs($this->coordinador)
        ->get(Silabos::getUrl())
        ->assertOk()
        ->assertSeeInOrder(['Sílabos', 'FRA-1-M', 'Francés · Ciclo 1', 'Sin docente', 'Falta', 'ING-1-B', 'Inglés · Ciclo 1', 'Paz Soto, Lucía', 'Borrador', 'ING-1-M', 'Vega Ríos, Carlos', 'Publicado', '10/03/2027 09:00'])
        ->assertDontSee('ING-1-X')
        ->assertDontSee('ING-2-A');
});

it('filters the groups by period, language, teacher and syllabus not published', function (string $filtro, Closure $valor, array $visibles, array $ocultos): void {
    $this->actingAs($this->coordinador);

    $pagina = Livewire::test(Silabos::class)->filterTable($filtro, $valor->call($this));

    foreach ($visibles as $codigo) {
        $pagina->assertSee($codigo);
    }

    foreach ($ocultos as $codigo) {
        $pagina->assertDontSee($codigo);
    }
})->with([
    'period' => ['periodo', fn (): int => $this->abril->id, ['ING-2-A'], ['ING-1-M', 'ING-1-B', 'FRA-1-M']],
    'language' => ['idioma', fn (): int => $this->frances->id, ['FRA-1-M'], ['ING-1-M', 'ING-1-B']],
    'teacher' => ['docente', fn (): int => $this->otroDocente->id, ['ING-1-B'], ['ING-1-M', 'FRA-1-M']],
    'not published' => ['sin_publicar', fn (): bool => true, ['ING-1-B', 'FRA-1-M'], ['ING-1-M']],
]);

it('defaults the period filter to the period in progress', function (): void {
    $this->actingAs($this->coordinador);

    Livewire::test(Silabos::class)->assertTableFilterExists('periodo')->assertSet('tableFilters.periodo.value', $this->publicado->periodo_id);
});

it('shows a syllabus with every section in a modal, and offers it only when the group has one', function (): void {
    $this->actingAs($this->coordinador);

    $pagina = Livewire::test(Silabos::class)
        ->assertActionHidden(TestAction::make('ver')->table((string) $this->falta->id))
        ->mountAction(TestAction::make('ver')->table((string) $this->publicado->id));

    $modal = modalDelSilabo($pagina);

    test()->assertThat([
        'Sílabo del grupo ING-1-M', 'I. Datos generales', '1.2. Idioma', 'Inglés', 'Básico I', 'Marzo 2027 (1 mar 2027 al 31 mar 2027)', '60', 'Vega Ríos, Carlos',
        'II. Sumilla', 'Curso básico de inglés.', 'V. Programación de contenidos', '1 – 7 mar', 'Unidad 1', 'Vocabulario 1', 'Diálogos 1',
        'VI. Estrategias metodológicas', 'Enfoque comunicativo.', 'VII. Evaluación', 'Examen parcial', '40', 'Indicadores de parcial', 'Rúbrica',
        'VIII. Referencias bibliográficas', '1. Libro del curso.',
    ], new SeeInOrder($modal));
    expect($modal)->not->toContain('Borrador: aún no publicado para los estudiantes.');
});

it('warns in the modal that a draft is not visible to students yet', function (): void {
    $this->actingAs($this->coordinador);

    $pagina = Livewire::test(Silabos::class)->mountAction(TestAction::make('ver')->table((string) $this->borrador->id));

    test()->assertThat(['Sílabo del grupo ING-1-B', 'Borrador: aún no publicado para los estudiantes.', 'Paz Soto, Lucía'], new SeeInOrder(modalDelSilabo($pagina)));
});

// Filament renders the mounted action's modal as a Livewire partial, outside the component HTML.
function modalDelSilabo(Testable $pagina): string
{
    $modales = $pagina->effects['partials']['action-modals'] ?? '';

    return is_string($modales) ? html_entity_decode($modales) : '';
}

it('opens the syllabus PDF in a new tab for coordination, drafts included', function (): void {
    Pdf::fake();

    $this->actingAs($this->coordinador)->get(route('panel.silabos.pdf', $this->borrador->id))->assertOk();
    Pdf::assertRespondedWithPdf(fn (PdfBuilder $pdf): bool => $pdf->contains('BORRADOR'));

    Livewire::actingAs($this->coordinador)
        ->test(Silabos::class)
        ->assertActionHasUrl(TestAction::make('pdf')->table((string) $this->publicado->id), route('panel.silabos.pdf', $this->publicado->id))
        ->assertActionShouldOpenUrlInNewTab(TestAction::make('pdf')->table((string) $this->publicado->id))
        ->assertActionHidden(TestAction::make('pdf')->table((string) $this->falta->id));
});

it('keeps the syllabus PDF away from staff without the teaching view permission and answers a group without syllabus', function (): void {
    $sinPermiso = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Reportar->value]);

    $this->actingAs($sinPermiso)->get(route('panel.silabos.pdf', $this->publicado->id))->assertForbidden();
    $this->actingAs($this->coordinador)->get(route('panel.silabos.pdf', $this->falta->id))->assertNotFound();
    auth()->logout();
    $this->get(route('panel.silabos.pdf', $this->publicado->id))->assertRedirect();
});

it('sends staff signed in outside the panel to the panel sign-in instead of serving the syllabus PDF', function (): void {
    $this->be($this->coordinador)->get(route('panel.silabos.pdf', $this->publicado->id))->assertRedirect('/admin/login');
    $this->assertGuest();

    $this->get(route('panel.silabos.pdf', $this->publicado->id))->assertRedirect(route('filament.admin.auth.login'));
});

it('refuses a staff download to a portal account without signing it out', function (): void {
    [$usuario] = PortalDocente::docente();

    $this->be($usuario)->get(route('panel.silabos.pdf', $this->publicado->id))->assertForbidden();
    $this->assertAuthenticatedAs($usuario);
});

it('refuses a staff download to a deactivated account even with an open panel session', function (): void {
    $this->coordinador->forceFill(['desactivado_en' => now()])->save();

    $this->actingAs($this->coordinador)->get(route('panel.silabos.pdf', $this->publicado->id))->assertForbidden();
});

it('sends staff to set up the second factor before a download when the panel requires it', function (): void {
    config(['auth.panel_doble_factor_obligatorio' => true]);

    $this->actingAs($this->coordinador)->get(route('panel.silabos.pdf', $this->publicado->id))
        ->assertRedirect(Filament::getPanel('admin')->getSetUpRequiredMultiFactorAuthenticationUrl());
});
