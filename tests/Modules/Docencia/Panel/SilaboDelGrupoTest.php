<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Docencia\Actions\GuardarSilabo;
use App\Modules\Docencia\Actions\PublicarSilabo;
use App\Modules\Docencia\Enums\EstadoSilabo;
use App\Modules\Docencia\Filament\Docente\Pages\SilaboDelGrupo;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\SemanaDeSilabo;
use App\Modules\Docencia\Models\Silabo;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Support\ActasFinales;
use Tests\Support\PortalDocente;

// ActasFinales::curso(): group ING-1-M of Inglés Básico I (60 hours), period 1 to 31 March 2027 (five weeks), scheme 40/40/20.
beforeEach(function (): void {
    Filament::setCurrentPanel('docente');
    $this->curso = ActasFinales::curso();
    $this->grupo = $this->curso['grupo'];
    $this->usuario = $this->curso['usuario'];
    $this->grupo->update(['aula_id' => Aula::factory()->create(['codigo' => 'A-101', 'nombre' => 'Laboratorio 1'])->id]);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
});

/**
 * The page form keys evaluation rows by component id.
 *
 * @return array<string, mixed>
 */
function silaboDelFormulario(string $prefijo = ''): array
{
    $datos = PortalDocente::silaboCompleto(5, ['parcial', 'final', 'participacion'], $prefijo);
    $datos['evaluaciones'] = collect($datos['evaluaciones'])->mapWithKeys(fn (array $fila, string $codigo): array => [test()->curso[$codigo]->id => $fila])->all();

    return $datos;
}

it('fills the identification from the group and gives one row per period week and per scheme component', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.silabo', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder([
            'Sílabo del grupo ING-1-M', 'Falta',
            'I. Identificación', 'Inglés', 'Básico I', 'Básico', 'Marzo 2027', '60', 'Lun, 18:00–20:00', 'A-101 · Laboratorio 1', 'Vega Ríos, Carlos',
            'II. Sumilla, competencias y capacidades', 'Sumilla',
            'III. Programación semanal', 'Semana 1 · 1 – 7 mar', 'Saber', 'Actividades', 'Semana 5 · 29 – 31 mar',
            'IV. Evaluación', 'Examen parcial · 40 %', 'Indicadores', 'Instrumento', 'Examen final · 40 %', 'Participación · 20 %',
            'V. Metodología y bibliografía',
        ])
        ->assertDontSee('Semana 6');

    Livewire::actingAs($this->usuario)
        ->test(SilaboDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSet('silabo', fn (array $silabo): bool => array_keys($silabo) === ['sumilla', 'competencias', 'capacidades', 'metodologia', 'bibliografia', 'semanas', 'evaluaciones']
            && array_keys($silabo['semanas']) === [1, 2, 3, 4, 5]
            && array_keys($silabo['evaluaciones']) === [$this->curso['parcial']->id, $this->curso['final']->id, $this->curso['participacion']->id]);
});

it('saves a draft from the form and loads it back', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(SilaboDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set('silabo.sumilla', ' Curso básico. ')
        ->set('silabo.semanas.2.saber', 'Saludos')
        ->set("silabo.evaluaciones.{$this->curso['final']->id}.semana", '5')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Borrador guardado.')
        ->assertSet('silabo.sumilla', 'Curso básico.');

    Livewire::actingAs($this->usuario)
        ->test(SilaboDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSet('silabo.semanas.2.saber', 'Saludos')
        ->assertSet("silabo.evaluaciones.{$this->curso['final']->id}.semana", '5');

    expect(Silabo::query()->sole()->only(['estado', 'sumilla']))->toBe(['estado' => EstadoSilabo::Borrador, 'sumilla' => 'Curso básico.'])
        ->and(SemanaDeSilabo::query()->sole()->saber)->toBe('Saludos');
});

it('shows and logs what is missing when publishing and keeps the draft', function (): void {
    Log::spy();

    Livewire::actingAs($this->usuario)
        ->test(SilaboDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set('silabo', [...silaboDelFormulario(), 'bibliografia' => ''])
        ->callAction('publicar')
        ->assertHasErrors('silabo')
        ->assertSee('Para publicar el sílabo completa: Bibliografía.')
        ->assertSet('silabo.bibliografia', '');

    expect(Silabo::query()->count())->toBe(0);
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', ['regla' => 'Para publicar el sílabo completa: Bibliografía.'])->once();
});

it('publishes a complete syllabus after confirmation and lets the teacher correct it', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-02-25 09:30', 'America/Lima'));

    $pagina = Livewire::actingAs($this->usuario)
        ->test(SilaboDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set('silabo', silaboDelFormulario())
        ->callAction('publicar')
        ->assertHasNoErrors()
        ->assertSee('Sílabo publicado.')
        ->assertActionHidden('publicar');

    $pagina->set('silabo.semanas.3.recursos', 'Fichas de vocabulario')
        ->callAction('guardar')
        ->assertSee('Cambios guardados. El sílabo sigue publicado.');

    expect(Silabo::query()->sole()->only(['estado', 'publicado_por']))->toBe(['estado' => EstadoSilabo::Publicado, 'publicado_por' => $this->usuario->id])
        ->and(SemanaDeSilabo::query()->where('semana', 3)->value('recursos'))->toBe('Fichas de vocabulario');
});

it('copies a published syllabus of the same cycle into the form after confirmation', function (): void {
    $otro = Grupo::factory()->create(['codigo' => 'ING-1-B', 'ciclo_id' => $this->grupo->ciclo_id, 'periodo_id' => $this->grupo->periodo_id]);
    PortalDocente::asignar($otro, $otroDocente = Docente::factory()->create());
    $origen = app(PublicarSilabo::class)($otro->id, $otroDocente->id, PortalDocente::silaboCompleto(5, ['parcial', 'final', 'participacion'], 'Copia: '), $this->usuario);

    Livewire::actingAs($this->usuario)
        ->test(SilaboDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set('silabo.sumilla', 'Lo que había escrito.')
        ->callAction('copiar', ['origen' => $origen->id])
        ->assertHasNoErrors()
        ->assertSee('Sílabo copiado. Revísalo y publícalo cuando esté listo.')
        ->assertSet('silabo.sumilla', 'Copia: Curso básico de inglés.')
        ->assertSet("silabo.evaluaciones.{$this->curso['parcial']->id}.indicadores", 'Copia: Indicadores de parcial');

    expect(Silabo::query()->where('grupo_id', $this->grupo->id)->sole()->estado)->toBe(EstadoSilabo::Borrador);
});

it('hides the syllabus of a group the teacher does not have now', function (): void {
    $pagina = Livewire::actingAs($this->usuario)->test(SilaboDelGrupo::class, ['grupo' => $this->grupo->id]);
    PortalDocente::asignar($this->grupo, Docente::factory()->create());

    $pagina->call('guardar')->assertNotFound();
    Livewire::actingAs($this->usuario)->test(SilaboDelGrupo::class, ['grupo' => $this->grupo->id])->assertNotFound();
    $this->actingAs($this->usuario)->get(route('filament.docente.pages.grupos.silabo', $this->grupo->id))->assertNotFound();
    $this->actingAs($this->usuario)->get('/docente/grupos/abc/silabo')->assertNotFound();

    expect(Silabo::query()->count())->toBe(0);
});

it('adds the syllabus tab to every page of the group', function (string $pagina): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-30 10:00', 'America/Lima'));

    $this->actingAs($this->usuario)
        ->get(route("filament.docente.pages.grupos.{$pagina}", $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder(['Asistencia', 'Pre-acta', 'Acta final', 'Sílabo'])
        ->assertSee(route('filament.docente.pages.grupos.silabo', $this->grupo->id))
        ->assertSee(route('filament.docente.pages.grupos.acta', $this->grupo->id));
})->with(['asistencia', 'notas', 'acta', 'silabo']);

it('shows on each group card whether its syllabus is missing, a draft or published', function (): void {
    $borrador = Grupo::factory()->create(['codigo' => 'ING-1-N', 'ciclo_id' => $this->grupo->ciclo_id, 'periodo_id' => $this->grupo->periodo_id]);
    $publicado = Grupo::factory()->create(['codigo' => 'ING-1-O', 'ciclo_id' => $this->grupo->ciclo_id, 'periodo_id' => $this->grupo->periodo_id]);
    PortalDocente::asignar($borrador, $this->curso['docente']);
    PortalDocente::asignar($publicado, $this->curso['docente']);
    app(GuardarSilabo::class)($borrador->id, $this->curso['docente']->id, ['sumilla' => 'Curso básico.']);
    app(PublicarSilabo::class)($publicado->id, $this->curso['docente']->id, PortalDocente::silaboCompleto(5, ['parcial', 'final', 'participacion']), $this->usuario);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertOk()
        ->assertSeeInOrder(['Grupo ING-1-M', 'Sílabo', 'Falta', 'Grupo ING-1-N', 'Sílabo', 'Borrador', 'Grupo ING-1-O', 'Sílabo', 'Publicado'])
        ->assertSee(route('filament.docente.pages.grupos.silabo', $publicado->id));
});
