<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Livewire\AsistenciaDelGrupo;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 12:00', 'America/Lima'));
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    PortalDocente::asignar($this->grupo, $this->docente);
    $this->ana = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Quispe', 'apellido_materno' => 'Rojas', 'nombres' => 'Ana'])->id]);
    $this->luis = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Álvarez', 'apellido_materno' => 'Mejía', 'nombres' => 'Luis'])->id]);
});

it('links the roll call from the list of groups', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('docente.grupos'))
        ->assertSee(route('docente.asistencia', $this->grupo->id));
});

it('offers the classes held so far, newest first, and the students in alphabetical order', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('docente.asistencia', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder(['Asistencia del grupo ING-01-A', 'Miércoles 10/03/2027 de 18:00 a 20:00', 'Lunes 08/03/2027 de 18:00 a 20:00', 'Miércoles 03/03/2027 de 18:00 a 20:00', 'Lunes 01/03/2027 de 18:00 a 20:00', 'Álvarez Mejía, Luis', 'Quispe Rojas, Ana'])
        ->assertDontSee('15/03/2027');
});

it('starts a new roll call with everyone present and saves the marked states', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSet('clase', '2027-03-10|18:00')
        ->assertSet('estados', [$this->luis->id => 'presente', $this->ana->id => 'presente'])
        ->set("estados.{$this->ana->id}", 'tardanza')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Asistencia del miércoles 10/03/2027 de 18:00 a 20:00 guardada.');

    $clase = ClaseDictada::query()->sole();

    expect($clase->fecha->toDateString())->toBe('2027-03-10')
        ->and($clase->registrado_por)->toBe($this->usuario->id)
        ->and(Asistencia::query()->where('clase_id', $clase->id)->pluck('estado', 'matricula_id')->map->value->all())->toEqualCanonicalizing([$this->ana->id => 'tardanza', $this->luis->id => 'presente']);
});

it('loads the saved states when an earlier class is chosen and marks it as recorded', function (): void {
    $clase = ClaseDictada::query()->create(['grupo_id' => $this->grupo->id, 'fecha' => '2027-03-08', 'hora_inicio' => '18:00', 'hora_fin' => '20:00', 'docente_id' => $this->docente->id, 'registrado_por' => $this->usuario->id]);
    Asistencia::query()->create(['clase_id' => $clase->id, 'matricula_id' => $this->ana->id, 'estado' => 'falta']);
    Asistencia::query()->create(['clase_id' => $clase->id, 'matricula_id' => $this->luis->id, 'estado' => 'presente']);

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSee('Lunes 08/03/2027 de 18:00 a 20:00 · registrada')
        ->set('clase', '2027-03-08|18:00')
        ->assertSet('estados', [$this->luis->id => 'presente', $this->ana->id => 'falta']);
});

it('hides a group the teacher does not have now', function (): void {
    PortalDocente::asignar($this->grupo, Docente::factory()->create());

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertNotFound();

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => Grupo::factory()->create()->id])
        ->assertNotFound();
});

it('shows the error when the teacher loses the group before saving', function (): void {
    Log::spy();
    $pagina = Livewire::actingAs($this->usuario)->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id]);
    PortalDocente::asignar($this->grupo, Docente::factory()->create());

    $pagina->call('guardar')->assertHasErrors(['asistencia' => 'No tienes a cargo el grupo ING-01-A.']);

    expect(ClaseDictada::query()->count())->toBe(0);
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', Mockery::on(fn (array $contexto): bool => $contexto['regla'] === 'No tienes a cargo el grupo ING-01-A.'))->once();
});

it('tells the teacher when no class has been held yet', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-02-20 12:00', 'America/Lima'));

    $this->actingAs($this->usuario)
        ->get(route('docente.asistencia', $this->grupo->id))
        ->assertOk()
        ->assertSee('Este grupo todavía no tiene clases para registrar.');
});
