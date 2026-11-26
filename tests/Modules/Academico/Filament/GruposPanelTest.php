<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Enums\PermisoAcademico;
use App\Modules\Academico\Filament\Resources\Grupos\GrupoResource;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\CreateGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\EditGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\ListGrupos;
use App\Modules\Academico\Filament\Resources\Grupos\Pages\ViewGrupo;
use App\Modules\Academico\Filament\Resources\Grupos\RelationManagers\HorariosRelationManager;
use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Academico\Models\Programa;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\DB;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;
use Tests\Support\OfertaAcademica;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoAcademico::valores()]);
    $this->periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027']);
    $this->idioma = Idioma::factory()->create(['nombre' => 'Inglés']);
    $this->ciclo = Ciclo::factory()->for($this->idioma)->create(['numero' => 3, 'nivel' => NivelCurricular::Basico]);
    $this->aula = Aula::factory()->create(['codigo' => 'A-101', 'capacidad' => 30]);
    $this->grupo = Grupo::factory()->create([
        'periodo_id' => $this->periodo->id, 'ciclo_id' => $this->ciclo->id, 'aula_id' => $this->aula->id,
        'codigo' => 'ING-03-A', 'cupo_maximo' => 25, 'estado' => EstadoGrupo::Abierto,
    ]);
    DB::table('aca_grupos')->where('id', $this->grupo->id)->update(['matriculados' => 5]);
});

function horariosDe(Grupo $grupo): Testable
{
    return Livewire::test(HorariosRelationManager::class, ['ownerRecord' => $grupo, 'pageClass' => EditGrupo::class]);
}

function formularioDeGrupo(array $cambios = []): array
{
    return [
        'periodo_id' => test()->periodo->id,
        'ciclo_id' => test()->ciclo->id,
        'codigo' => 'ING-03-B',
        'modalidad' => Modalidad::Presencial->value,
        'es_acelerado' => false,
        'aula_id' => test()->aula->id,
        'cupo_maximo' => 20,
        'cupo_minimo' => 8,
        'programa_id' => Programa::query()->where('codigo', 'JA')->value('id'),
        ...$cambios,
    ];
}

it('keeps the groups away from users without the academic read permission', function (): void {
    $ajeno = User::factory()->create()->givePermissionTo(User::ADMIN_PANEL_PERMISSION);

    $this->actingAs($ajeno)->get(GrupoResource::getUrl('index'))->assertForbidden();
    $this->actingAs($ajeno)->get(GrupoResource::getUrl('edit', ['record' => $this->grupo]))->assertForbidden();
});

it('lists groups with their offer, classroom, quota and vacancies', function (): void {
    $virtual = Grupo::factory()->create([
        'periodo_id' => $this->periodo->id, 'ciclo_id' => $this->ciclo->id, 'modalidad' => Modalidad::Virtual,
        'enlace_virtual' => 'https://meet.example.test/v', 'estado' => EstadoGrupo::Programado,
    ]);
    $this->actingAs($this->gestor);

    Livewire::test(ListGrupos::class)
        ->assertCanSeeTableRecords([$this->grupo, $virtual])
        ->assertSeeHtml('data-idioma="Inglés"')
        ->assertSeeText('Inglés · Ciclo 3 (Básico)')
        ->assertTableColumnFormattedStateSet('modalidad', 'Presencial', $this->grupo)
        ->assertTableColumnStateSet('aula.codigo', 'A-101', $this->grupo)
        ->assertTableColumnStateSet('matriculados', 5, $this->grupo)
        ->assertTableColumnStateSet('vacantes_disponibles', 20, $this->grupo)
        ->assertTableColumnFormattedStateSet('estado', 'Abierto', $this->grupo)
        ->assertTableColumnFormattedStateSet('estado', 'Programado', $virtual);
});

it('filters groups by period, state, modality and language', function (): void {
    $otroIdioma = Ciclo::factory()->create();
    $otro = Grupo::factory()->create([
        'ciclo_id' => $otroIdioma->id, 'modalidad' => Modalidad::Virtual,
        'enlace_virtual' => 'https://meet.example.test/o', 'estado' => EstadoGrupo::Programado,
    ]);
    $this->actingAs($this->gestor);

    Livewire::test(ListGrupos::class)
        ->filterTable('periodo', $this->periodo->id)->assertCanSeeTableRecords([$this->grupo])->assertCanNotSeeTableRecords([$otro])
        ->resetTableFilters()
        ->filterTable('estado', EstadoGrupo::Programado->value)->assertCanSeeTableRecords([$otro])->assertCanNotSeeTableRecords([$this->grupo])
        ->resetTableFilters()
        ->filterTable('modalidad', Modalidad::Presencial->value)->assertCanSeeTableRecords([$this->grupo])->assertCanNotSeeTableRecords([$otro])
        ->resetTableFilters()
        ->filterTable('idioma', $this->idioma->id)->assertCanSeeTableRecords([$this->grupo])->assertCanNotSeeTableRecords([$otro]);
});

it('creates a group through the action as scheduled', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreateGrupo::class)
        ->fillForm(formularioDeGrupo(['es_acelerado' => true]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Grupo::query()->where('codigo', 'ING-03-B')->sole())
        ->aula_id->toBe($this->aula->id)
        ->es_acelerado->toBeTrue()
        ->estado->toBe(EstadoGrupo::Programado)
        ->matriculados->toBe(0);
});

it('creates a group in the chosen program and refuses a regular group in Posgrado', function (): void {
    $posgrado = Programa::query()->where('codigo', 'POSGRADO')->sole();
    $this->actingAs($this->gestor);

    Livewire::test(CreateGrupo::class)
        ->fillForm(formularioDeGrupo(['programa_id' => $posgrado->id]))
        ->call('create')
        ->assertNotified('El programa Posgrado solo admite grupos acelerados.');

    Livewire::test(CreateGrupo::class)
        ->fillForm(formularioDeGrupo(['programa_id' => $posgrado->id, 'es_acelerado' => true]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Grupo::query()->where('codigo', 'ING-03-B')->sole()->programa_id)->toBe($posgrado->id);

    Livewire::test(ListGrupos::class)->assertTableColumnStateSet('programa.nombre', 'Posgrado', Grupo::query()->where('codigo', 'ING-03-B')->sole());
});

it('shows a broken group rule as a notification and keeps the form', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreateGrupo::class)
        ->fillForm(formularioDeGrupo(['cupo_maximo' => 35]))
        ->call('create')
        ->assertNotified('El aula A-101 tiene capacidad para 30 estudiantes; el cupo máximo no puede superarla.');

    expect(Grupo::query()->where('codigo', 'ING-03-B')->exists())->toBeFalse();
});

it('creates a virtual group with its link and no classroom', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(CreateGrupo::class)
        ->fillForm(formularioDeGrupo(['modalidad' => Modalidad::Virtual->value, 'aula_id' => null, 'enlace_virtual' => 'https://meet.example.test/b', 'cupo_maximo' => 40]))
        ->call('create')
        ->assertHasNoFormErrors();

    expect(Grupo::query()->where('codigo', 'ING-03-B')->sole())
        ->aula_id->toBeNull()
        ->enlace_virtual->toBe('https://meet.example.test/b');
});

it('edits a group through the action and refuses a quota below the enrolled students', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(EditGrupo::class, ['record' => $this->grupo->getRouteKey()])
        ->assertActionDoesNotExist('delete')
        ->fillForm(['cupo_maximo' => 4, 'cupo_minimo' => 2])
        ->call('save')
        ->assertNotified('El cupo máximo no puede ser menor que los 5 estudiantes matriculados.');
    expect($this->grupo->refresh()->cupo_maximo)->toBe(25);

    Livewire::test(EditGrupo::class, ['record' => $this->grupo->getRouteKey()])
        ->fillForm(['codigo' => 'ING-03-Z', 'cupo_maximo' => 28])
        ->call('save')
        ->assertHasNoFormErrors();
    expect($this->grupo->refresh())
        ->codigo->toBe('ING-03-Z')
        ->cupo_maximo->toBe(28)
        ->matriculados->toBe(5);
});

it('adds, edits and removes schedule lines with weekday names', function (): void {
    OfertaAcademica::matricular($this->grupo, 0);
    $this->actingAs($this->gestor);

    horariosDe($this->grupo)
        ->callAction(TestAction::make('agregarHorario')->table(), data: ['dia_semana' => 2, 'hora_inicio' => '18:00', 'hora_fin' => '20:00'])
        ->assertHasNoFormErrors()
        ->assertNotified('Horario agregado');
    $horario = $this->grupo->horarios()->sole();
    expect($horario)->dia_semana->toBe(2)->hora_inicio->toBe('18:00:00');

    horariosDe($this->grupo)
        ->assertTableColumnFormattedStateSet('dia_semana', 'Martes', $horario)
        ->assertTableColumnFormattedStateSet('hora_inicio', '18:00', $horario)
        ->callAction(TestAction::make('editarHorario')->table($horario), data: ['dia_semana' => 4, 'hora_inicio' => '17:00', 'hora_fin' => '19:00'])
        ->assertNotified('Horario actualizado');
    expect($horario->refresh())->dia_semana->toBe(4)->hora_fin->toBe('19:00:00');

    horariosDe($this->grupo)->callAction(TestAction::make('delete')->table($horario));
    expect(Horario::query()->whereKey($horario->id)->exists())->toBeFalse();
});

it('explains a classroom clash when adding a schedule line', function (): void {
    $ocupante = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'aula_id' => $this->aula->id, 'codigo' => 'FRA-01']);
    Horario::factory()->for($ocupante)->create(['dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    OfertaAcademica::matricular($this->grupo, 0);
    $this->actingAs($this->gestor);

    horariosDe($this->grupo)
        ->callAction(TestAction::make('agregarHorario')->table(), data: ['dia_semana' => 1, 'hora_inicio' => '09:00', 'hora_fin' => '11:00'])
        ->assertNotified('El aula A-101 ya está ocupada por el grupo FRA-01 el Lunes de 08:00 a 10:00.');

    expect($this->grupo->horarios()->count())->toBe(0);
});

it('offers each state change only where the transition is allowed', function (): void {
    $programado = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'estado' => EstadoGrupo::Programado]);
    $cerrado = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'estado' => EstadoGrupo::Cerrado]);
    $finalizado = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'estado' => EstadoGrupo::Finalizado]);
    $this->actingAs($this->gestor);

    Livewire::test(ListGrupos::class)
        ->assertActionVisible(TestAction::make('abrir')->table($programado))
        ->assertActionHidden(TestAction::make('cerrar')->table($programado))
        ->assertActionVisible(TestAction::make('cancelar')->table($programado))
        ->assertActionHidden(TestAction::make('abrir')->table($this->grupo))
        ->assertActionVisible(TestAction::make('cerrar')->table($this->grupo))
        ->assertActionHidden(TestAction::make('finalizar')->table($this->grupo))
        ->assertActionVisible(TestAction::make('abrir')->table($cerrado))
        ->assertActionVisible(TestAction::make('finalizar')->table($cerrado))
        ->assertActionHidden(TestAction::make('abrir')->table($finalizado))
        ->assertActionHidden(TestAction::make('cancelar')->table($finalizado));
});

it('changes the state from the group page and explains a refused cancellation', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(EditGrupo::class, ['record' => $this->grupo->getRouteKey()])
        ->callAction('cancelar')
        ->assertNotified('No se puede cancelar un grupo con 5 estudiantes matriculados.');
    expect($this->grupo->refresh()->estado)->toBe(EstadoGrupo::Abierto);

    Livewire::test(EditGrupo::class, ['record' => $this->grupo->getRouteKey()])
        ->callAction('cerrar')
        ->assertNotified('Grupo cerrado');
    expect($this->grupo->refresh()->estado)->toBe(EstadoGrupo::Cerrado);
});

it('lets read-only users view a group and its schedule but hides every change', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoAcademico::Ver->value]);
    Horario::factory()->for($this->grupo)->create(['dia_semana' => 6, 'hora_inicio' => '09:00', 'hora_fin' => '12:00']);
    $this->actingAs($lector);

    $this->get(GrupoResource::getUrl('view', ['record' => $this->grupo]))->assertOk()->assertSee('ING-03-A');
    $this->get(GrupoResource::getUrl('edit', ['record' => $this->grupo]))->assertForbidden();

    Livewire::test(ListGrupos::class)
        ->assertCanSeeTableRecords([$this->grupo])
        ->assertActionHidden(TestAction::make('cerrar')->table($this->grupo))
        ->assertActionHidden(TestAction::make('cancelar')->table($this->grupo))
        ->assertActionHidden('create');

    horariosDe($this->grupo)->assertActionHidden(TestAction::make('agregarHorario')->table());
});

it('does not offer cycles of an inactive language for new groups', function (): void {
    $this->idioma->update(['activo' => false]);
    $this->actingAs($this->gestor);

    Livewire::test(CreateGrupo::class)
        ->fillForm(formularioDeGrupo())
        ->call('create')
        ->assertHasFormErrors(['ciclo_id']);

    expect(Grupo::query()->where('codigo', 'ING-03-B')->exists())->toBeFalse();
});

it('edits a group whose classroom was deactivated, keeping that classroom', function (): void {
    $this->aula->update(['activo' => false]);
    $this->actingAs($this->gestor);

    Livewire::test(EditGrupo::class, ['record' => $this->grupo->getRouteKey()])
        ->fillForm(['cupo_maximo' => 20])
        ->call('save')
        ->assertHasNoFormErrors();

    expect($this->grupo->refresh())
        ->cupo_maximo->toBe(20)
        ->aula_id->toBe($this->aula->id);
});

it('hides schedule changes on a finished group', function (): void {
    $horario = Horario::factory()->for($this->grupo)->create(['dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    DB::table('aca_grupos')->where('id', $this->grupo->id)->update(['estado' => EstadoGrupo::Finalizado->value]);
    $this->actingAs($this->gestor);

    horariosDe($this->grupo->refresh())
        ->assertActionHidden(TestAction::make('agregarHorario')->table())
        ->assertActionHidden(TestAction::make('editarHorario')->table($horario))
        ->assertActionHidden(TestAction::make('delete')->table($horario));
});

it('explains why the schedule of a group with enrolled students cannot change', function (): void {
    $horario = Horario::factory()->for($this->grupo)->create(['dia_semana' => 2, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    $this->actingAs($this->gestor);
    $mensaje = 'El horario del grupo ING-03-A no puede cambiar porque ya tiene estudiantes matriculados o clases dictadas.';

    horariosDe($this->grupo)
        ->callAction(TestAction::make('agregarHorario')->table(), data: ['dia_semana' => 4, 'hora_inicio' => '18:00', 'hora_fin' => '20:00'])
        ->assertNotified($mensaje);
    horariosDe($this->grupo)
        ->callAction(TestAction::make('delete')->table($horario))
        ->assertNotified($mensaje);

    expect($this->grupo->horarios()->get()->modelKeys())->toBe([$horario->id]);
});

it('lists what a group is missing when it cannot be opened', function (): void {
    $programado = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'codigo' => 'ING-03-P', 'estado' => EstadoGrupo::Programado]);
    $this->actingAs($this->gestor);

    Livewire::test(EditGrupo::class, ['record' => $programado->getRouteKey()])
        ->callAction('abrir')
        ->assertNotified('No se puede abrir el grupo ING-03-P: no tiene horario; no tiene docente asignado.');

    expect($programado->refresh()->estado)->toBe(EstadoGrupo::Programado);
});

it('shows the enrolled students and the minimum quota before closing a group below it', function (): void {
    $this->actingAs($this->gestor);

    Livewire::test(EditGrupo::class, ['record' => $this->grupo->getRouteKey()])
        ->mountAction('cerrar')
        ->assertMountedActionModalSee('El grupo tiene 5 matriculados y el mínimo de apertura es 10. ¿Iniciar de todas formas?');
});

it('offers no edit for a cancelled or finished group and forbids its edit page', function (EstadoGrupo $estado): void {
    $abierto = Grupo::factory()->create(['periodo_id' => $this->periodo->id, 'ciclo_id' => $this->ciclo->id, 'codigo' => 'ING-03-Z', 'estado' => EstadoGrupo::Abierto]);
    DB::table('aca_grupos')->where('id', $this->grupo->id)->update(['estado' => $estado->value]);
    $this->actingAs($this->gestor);

    Livewire::test(ListGrupos::class)
        ->assertActionHidden(TestAction::make('edit')->table($this->grupo))
        ->assertActionVisible(TestAction::make('edit')->table($abierto));
    Livewire::test(ViewGrupo::class, ['record' => $this->grupo->getRouteKey()])->assertActionHidden('edit');
    Livewire::test(ViewGrupo::class, ['record' => $abierto->getRouteKey()])->assertActionVisible('edit');
    $this->get(GrupoResource::getUrl('edit', ['record' => $this->grupo]))->assertForbidden();
    $this->get(GrupoResource::getUrl('edit', ['record' => $abierto]))->assertOk();
})->with([EstadoGrupo::Cancelado, EstadoGrupo::Finalizado]);
