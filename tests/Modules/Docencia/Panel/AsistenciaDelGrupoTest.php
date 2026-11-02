<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Filament\Docente\Pages\AsistenciaDelGrupo;
use App\Modules\Docencia\Models\Asistencia;
use App\Modules\Docencia\Models\ClaseDictada;
use App\Modules\Docencia\Models\Delegado;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use Carbon\CarbonImmutable;
use Filament\Facades\Filament;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Log;
use Livewire\Livewire;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    Filament::setCurrentPanel('docente');
    $this->travelTo(CarbonImmutable::parse('2027-03-10 18:05', 'America/Lima'));
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $periodo = Periodo::factory()->create(['fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-01-A', 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 3, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    PortalDocente::asignar($this->grupo, $this->docente);
    $this->ana = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Quispe', 'apellido_materno' => 'Rojas', 'nombres' => 'Ana'])->id, 'matriculado_en' => '2027-02-20 15:00:00+00']);
    $this->luis = Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Álvarez', 'apellido_materno' => 'Mejía', 'nombres' => 'Luis'])->id, 'matriculado_en' => '2027-02-20 15:00:00+00']);
});

it('links the roll call from the list of groups', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertSee(route('filament.docente.pages.grupos.asistencia', $this->grupo->id));
});

it('offers the classes held so far, newest first, and the students in alphabetical order', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.asistencia', $this->grupo->id))
        ->assertOk()
        ->assertSeeInOrder(['Asistencia del grupo ING-01-A', 'Álvarez Mejía, Luis', 'Quispe Rojas, Ana']);

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertFormFieldExists('clase', fn (Select $campo): bool => $campo->getOptions() === [
            '2027-03-10|18:00' => 'Miércoles 10/03/2027 de 18:00 a 20:00',
            '2027-03-08|18:00' => 'Lunes 08/03/2027 de 18:00 a 20:00',
            '2027-03-03|18:00' => 'Miércoles 03/03/2027 de 18:00 a 20:00',
            '2027-03-01|18:00' => 'Lunes 01/03/2027 de 18:00 a 20:00',
        ]);
});

it('starts a new roll call with everyone present and saves the marked states', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSet('clase', '2027-03-10|18:00')
        ->assertSet('estados', [$this->luis->id => 'presente', $this->ana->id => 'presente'])
        ->set("estados.{$this->ana->id}", 'falta')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Llamado de inicio del miércoles 10/03/2027 de 18:00 a 20:00 guardado.');

    $clase = ClaseDictada::query()->sole();

    expect($clase->fecha->toDateString())->toBe('2027-03-10')
        ->and($clase->llamado_inicio_en?->setTimezone('America/Lima')->format('H:i'))->toBe('18:05')
        ->and($clase->registrado_por)->toBe($this->usuario->id)
        ->and(Asistencia::query()->where('clase_id', $clase->id)->pluck('estado', 'matricula_id')->map->value->all())->toEqualCanonicalizing([$this->ana->id => 'falta', $this->luis->id => 'presente']);
});

it('shows the two calls of a class as steps, the opening one first', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSeeHtml('data-pasos-del-llamado')
        ->assertSeeInOrder(['1', 'Llamado de inicio', 'En curso', '2', 'Llamado de cierre', 'Pendiente']);
});

it('loads the saved states when an earlier class is chosen and marks it as recorded', function (): void {
    $clase = ClaseDictada::query()->create(['grupo_id' => $this->grupo->id, 'fecha' => '2027-03-08', 'hora_inicio' => '18:00', 'hora_fin' => '20:00', 'docente_id' => $this->docente->id, 'registrado_por' => $this->usuario->id]);
    Asistencia::query()->create(['clase_id' => $clase->id, 'matricula_id' => $this->ana->id, 'estado' => 'falta']);
    Asistencia::query()->create(['clase_id' => $clase->id, 'matricula_id' => $this->luis->id, 'estado' => 'presente']);

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertFormFieldExists('clase', fn (Select $campo): bool => $campo->getOptions()['2027-03-08|18:00'] === 'Lunes 08/03/2027 de 18:00 a 20:00 · registrada')
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

it('shows and logs a rejected roll call', function (): void {
    Log::spy();
    $pagina = Livewire::actingAs($this->usuario)->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id]);
    $this->grupo->update(['estado' => EstadoGrupo::Finalizado]);
    $mensaje = 'El grupo ING-01-A está Finalizado; la asistencia solo se registra en grupos abiertos o cerrados.';

    $pagina->call('guardar')->assertHasErrors(['asistencia' => $mensaje])->assertSee($mensaje);

    expect(ClaseDictada::query()->count())->toBe(0);
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', Mockery::on(fn (array $contexto): bool => $contexto['regla'] === $mensaje))->once();
});

it('stops showing the attendance once the group moves to another teacher', function (): void {
    $pagina = Livewire::actingAs($this->usuario)->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id]);
    PortalDocente::asignar($this->grupo, Docente::factory()->create());

    $pagina->set('clase', '2027-03-08|18:00')->assertNotFound();
});

it('lists for an earlier class only the students enrolled by then', function (): void {
    Matricula::factory()->create(['grupo_id' => $this->grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Zapata', 'nombres' => 'Rosa'])->id, 'matriculado_en' => '2027-03-09 15:00:00+00']);

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSee('Zapata')
        ->set('clase', '2027-03-08|18:00')
        ->assertDontSee('Zapata')
        ->assertSet('estados', [$this->luis->id => 'presente', $this->ana->id => 'presente']);
});

it('tells the teacher when no class has been held yet', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-02-20 12:00', 'America/Lima'));

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.grupos.asistencia', $this->grupo->id))
        ->assertOk()
        ->assertSee('Este grupo todavía no tiene clases para registrar.');
});

it('ignores a class that is not in the schedule instead of failing', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set('clase', 'no-es-fecha|18:00')
        ->assertOk()
        ->assertSee('Quispe Rojas, Ana')
        ->call('guardar')
        ->assertHasErrors('asistencia')
        ->assertSee('La fecha o la hora de la clase no es válida.');

    expect(ClaseDictada::query()->count())->toBe(0);
});

it('refuses a class key with extra parts instead of saving the class it starts with', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set('clase', '2027-03-10|18:00|x')
        ->call('guardar')
        ->assertSee('La fecha o la hora de la clase no es válida.');

    expect(ClaseDictada::query()->count())->toBe(0);
});

it('does not offer the class of today before its first call opens', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 17:49', 'America/Lima'));

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSet('clase', '2027-03-08|18:00')
        ->assertFormFieldExists('clase', fn (Select $campo): bool => ! array_key_exists('2027-03-10|18:00', $campo->getOptions()));
});

it('labels each class with its state', function (): void {
    $clase = fn (string $fecha, array $datos = []): ClaseDictada => ClaseDictada::query()->create(['grupo_id' => $this->grupo->id, 'fecha' => $fecha, 'hora_inicio' => '18:00', 'hora_fin' => '20:00', 'docente_id' => $this->docente->id, 'registrado_por' => $this->usuario->id, ...$datos]);
    $clase('2027-03-10', ['llamado_inicio_en' => now()]);
    $clase('2027-03-08', ['registro_posterior' => true]);
    $clase('2027-03-03', ['llamado_inicio_en' => '2027-03-03 23:00:00+00', 'llamado_cierre_en' => '2027-03-04 00:50:00+00', 'confirmacion' => 'confirmada', 'confirmada_por_matricula_id' => $this->ana->id, 'confirmada_en' => '2027-03-04 01:00:00+00']);

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertFormFieldExists('clase', fn (Select $campo): bool => $campo->getOptions() === [
            '2027-03-10|18:00' => 'Miércoles 10/03/2027 de 18:00 a 20:00 · con llamado de inicio',
            '2027-03-08|18:00' => 'Lunes 08/03/2027 de 18:00 a 20:00 · registro posterior',
            '2027-03-03|18:00' => 'Miércoles 03/03/2027 de 18:00 a 20:00 · confirmada',
            '2027-03-01|18:00' => 'Lunes 01/03/2027 de 18:00 a 20:00',
        ]);
});

it('takes the second call only for the students present at the start', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set("estados.{$this->ana->id}", 'falta')
        ->call('guardar');
    $this->travelTo(CarbonImmutable::parse('2027-03-10 19:50', 'America/Lima'));

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSee('Guardar llamado de cierre')
        ->assertDontSee('Quispe Rojas, Ana')
        ->assertSet('estados', [$this->luis->id => 'presente'])
        ->set("estados.{$this->luis->id}", 'falta')
        ->set('tema', 'Saludos y presentaciones')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Llamado de cierre del miércoles 10/03/2027 de 18:00 a 20:00 guardado.');

    $clase = ClaseDictada::query()->sole();

    expect($clase->llamado_cierre_en?->setTimezone('America/Lima')->format('H:i'))->toBe('19:50')
        ->and($clase->confirmacion?->value)->toBe('pendiente')
        ->and(Asistencia::query()->where('clase_id', $clase->id)->pluck('estado_cierre', 'matricula_id')->map(fn ($estado): ?string => $estado?->value)->all())
        ->toEqualCanonicalizing([$this->ana->id => null, $this->luis->id => 'falta']);
});

it('records an earlier class not taken in its windows as registro posterior', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set('clase', '2027-03-08|18:00')
        ->assertSee('Registro posterior: un solo llamado, sin confirmación del delegado.')
        ->assertSee('Guardar registro posterior')
        ->set('tema', 'El alfabeto')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Registro posterior del lunes 08/03/2027 de 18:00 a 20:00 guardado.');

    expect(ClaseDictada::query()->sole()->only(['registro_posterior', 'llamado_inicio_en', 'confirmacion', 'tema']))->toBe(['registro_posterior' => true, 'llamado_inicio_en' => null, 'confirmacion' => null, 'tema' => 'El alfabeto']);
});

it('shows a class whose calls are over without a way to save it', function (): void {
    Livewire::actingAs($this->usuario)->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])->call('guardar');
    $this->travelTo(CarbonImmutable::parse('2027-03-10 20:31', 'America/Lima'));

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSee('Los llamados de esta clase ya cerraron.')
        ->assertDontSee('Guardar llamado')
        ->assertSee('Quispe Rojas, Ana');
});

it('designates the delegate of the group from the attendance page', function (): void {
    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->assertSee('Este grupo todavía no tiene delegado.')
        ->callAction('designarDelegado', data: ['matricula' => $this->ana->id])
        ->assertHasNoErrors()
        ->assertSee('Delegado del grupo: Quispe Rojas, Ana.');

    expect(Delegado::query()->sole()->only(['grupo_id', 'matricula_id', 'designado_por']))->toBe(['grupo_id' => $this->grupo->id, 'matricula_id' => $this->ana->id, 'designado_por' => $this->usuario->id]);
});

it('shows and logs a rejected delegate designation', function (): void {
    Log::spy();
    $pagina = Livewire::actingAs($this->usuario)->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id]);
    $this->grupo->update(['estado' => EstadoGrupo::Finalizado]);
    $mensaje = 'El grupo ING-01-A está Finalizado; el delegado solo se designa en grupos abiertos o cerrados.';

    $pagina->callAction('designarDelegado', data: ['matricula' => $this->ana->id])->assertHasErrors(['asistencia' => $mensaje])->assertSee($mensaje);

    expect(Delegado::query()->count())->toBe(0);
    Log::shouldHaveReceived('info')->with('Regla de negocio incumplida.', Mockery::on(fn (array $contexto): bool => $contexto['regla'] === $mensaje))->once();
});

it('corrects a class saved before the two calls as plain attendance, not as registro posterior', function (): void {
    $clase = ClaseDictada::query()->create(['grupo_id' => $this->grupo->id, 'fecha' => '2027-03-08', 'hora_inicio' => '18:00', 'hora_fin' => '20:00', 'docente_id' => $this->docente->id, 'registrado_por' => $this->usuario->id]);
    Asistencia::query()->create(['clase_id' => $clase->id, 'matricula_id' => $this->ana->id, 'estado' => 'presente']);
    Asistencia::query()->create(['clase_id' => $clase->id, 'matricula_id' => $this->luis->id, 'estado' => 'presente']);

    Livewire::actingAs($this->usuario)
        ->test(AsistenciaDelGrupo::class, ['grupo' => $this->grupo->id])
        ->set('clase', '2027-03-08|18:00')
        ->assertDontSee('Registro posterior: un solo llamado')
        ->set("estados.{$this->luis->id}", 'falta')
        ->call('guardar')
        ->assertHasNoErrors()
        ->assertSee('Asistencia del lunes 08/03/2027 de 18:00 a 20:00 guardada.');

    expect($clase->refresh())->registro_posterior->toBeFalse()->llamado_inicio_en->toBeNull()
        ->and(Asistencia::query()->where('matricula_id', $this->luis->id)->value('estado')->value)->toBe('falta');
});
