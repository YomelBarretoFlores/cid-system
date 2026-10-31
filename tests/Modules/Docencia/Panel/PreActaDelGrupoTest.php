<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Filament\Docente\Pages\PreActaDelGrupo;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Docencia\Models\NotaParcial;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use Filament\Facades\Filament;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    Filament::setCurrentPanel('docente');
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'periodo_id' => Periodo::factory()->create(['fecha_inicio' => '2027-03-01'])->id, 'estado' => EstadoGrupo::Abierto]);
    PortalDocente::asignar($this->grupo, $this->docente);
    $esquema = EsquemaEvaluacion::factory()->create(['nombre' => 'Esquema general', 'nota_maxima' => '20.00', 'vigente_desde' => '2027-01-01']);
    $this->parcial = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '40.00', 'orden' => 1]);
    $this->final = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'nombre' => 'Examen final', 'peso' => '60.00', 'orden' => 2]);
    $this->ana = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Quispe', 'apellido_materno' => 'Rojas', 'nombres' => 'Ana'])->id]);
    $this->luis = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Álvarez', 'apellido_materno' => 'Mejía', 'nombres' => 'Luis'])->id]);
});

it('links the pre-acta from the list of groups', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertSee(route('filament.docente.pages.grupos.notas', $this->grupo->id));
});

it('shows the scheme components with their weights and the students in alphabetical order', function (): void {
    NotaParcial::query()->create(['grupo_id' => $this->grupo->id, 'matricula_id' => $this->ana->id, 'componente_id' => $this->parcial->id, 'nota' => '14.50', 'registrado_por' => $this->usuario->id]);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.notas', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder(['Pre-acta del grupo ING-01-A', 'Esquema general · notas de 0 a 20', 'Examen parcial', '40 %', 'Examen final', '60 %', 'Álvarez Mejía, Luis', 'Quispe Rojas, Ana']);

    Livewire::actingAs($this->usuario)
        ->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSet("notas.{$this->ana->id}.{$this->parcial->id}", '14.50')
        ->assertSet("notas.{$this->ana->id}.{$this->final->id}", '');
});

it('saves the grades typed by the teacher', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set("notas.{$this->ana->id}.{$this->parcial->id}", '15')
        ->set("notas.{$this->luis->id}.{$this->final->id}", '12,5')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Notas parciales guardadas.')
        ->assertSet("notas.{$this->luis->id}.{$this->final->id}", '12.50');

    expect(NotaParcial::query()->orderBy('nota')->pluck('nota')->all())->toBe(['12.50', '15.00']);
});

it('shows and logs a rejected grade and saves nothing', function (): void {
    Log::spy();

    Livewire::actingAs($this->usuario)
        ->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set("notas.{$this->ana->id}.{$this->parcial->id}", '15')
        ->set("notas.{$this->luis->id}.{$this->parcial->id}", '25')
        ->call('guardar')
        ->assertHasErrors('notas')
        ->assertSee('La nota 25 no es válida: usa un número de 0 a 20 con hasta dos decimales.');

    expect(NotaParcial::query()->count())->toBe(0);
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', ['regla' => 'La nota 25 no es válida: usa un número de 0 a 20 con hasta dos decimales.'])->once();
});

it('tells the teacher when the group has no evaluation scheme', function (): void {
    EsquemaEvaluacion::query()->update(['vigente_desde' => '2027-03-02']);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.notas', $this->grupo->id))
        ->assertOk()
        ->assertSee('El grupo no tiene un sistema de evaluación vigente; pide a coordinación que lo registre.')
        ->assertDontSee('Guardar notas');
});

it('hides the pre-acta of a group the teacher does not have now', function (): void {
    $pagina = Livewire::actingAs($this->usuario)->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id]);
    PortalDocente::asignar($this->grupo, Docente::factory()->create());

    $pagina->call('guardar')->assertNotFound();
    Livewire::actingAs($this->usuario)->test(PreActaDelGrupo::class, ['grupo' => $this->grupo->id])->assertNotFound();

    expect(NotaParcial::query()->count())->toBe(0);
});
