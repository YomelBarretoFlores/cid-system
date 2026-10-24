<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Actions\AsignarDocente;
use App\Modules\Docencia\Enums\PermisoDocencia;
use App\Modules\Docencia\Filament\Resources\Docentes\Pages\ViewDocente;
use App\Modules\Docencia\Filament\Resources\Docentes\RelationManagers\CargasRelationManager;
use App\Modules\Docencia\Models\CargaAcademica;
use App\Modules\Docencia\Models\Disponibilidad;
use App\Modules\Docencia\Models\Docente;
use App\Shared\Auth\Models\User;
use Filament\Actions\Testing\TestAction;
use Livewire\Features\SupportTesting\Testable;
use Livewire\Livewire;

beforeEach(function (): void {
    $this->gestor = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, ...PermisoDocencia::valores()]);
    $ingles = Idioma::factory()->create(['nombre' => 'Inglés']);
    $ciclo = Ciclo::factory()->for($ingles)->create(['numero' => 2, 'nivel' => NivelCurricular::Basico]);
    $periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-04-25']);
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-02-A', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->create(['grupo_id' => $this->grupo->id, 'dia_semana' => 2, 'hora_inicio' => '08:00:00', 'hora_fin' => '10:00:00']);
    $this->choque = Grupo::factory()->create(['codigo' => 'ING-02-B', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Abierto]);
    Horario::factory()->create(['grupo_id' => $this->choque->id, 'dia_semana' => 2, 'hora_inicio' => '09:00:00', 'hora_fin' => '11:00:00']);
    $this->docente = Docente::factory()->create();
    $this->docente->idiomas()->create(['idioma_id' => $ingles->id, 'nivel_maximo' => NivelCurricular::Basico]);
    Disponibilidad::query()->create(['docente_id' => $this->docente->id, 'dia_semana' => 2, 'hora_inicio' => '07:00:00', 'hora_fin' => '12:00:00']);
});

function cargaDe(Docente $docente, User $usuario): Testable
{
    return Livewire::actingAs($usuario)->test(CargasRelationManager::class, ['ownerRecord' => $docente, 'pageClass' => ViewDocente::class]);
}

it('assigns a group from the teacher profile and shows its load and the period total', function (): void {
    cargaDe($this->docente, $this->gestor)
        ->callAction(TestAction::make('asignarGrupo')->table(), data: ['grupo_id' => $this->grupo->id])
        ->assertNotified('Grupo asignado al docente');

    expect(CargaAcademica::query()->sole()->only(['grupo_id', 'docente_id', 'asignado_por']))
        ->toBe(['grupo_id' => $this->grupo->id, 'docente_id' => $this->docente->id, 'asignado_por' => $this->gestor->id]);

    cargaDe($this->docente, $this->gestor)
        ->assertSee(['ING-02-A', 'Inglés · Básico', 'Marzo 2027', '01/03/2027 – 25/04/2027', 'Martes', '16 h', 'Total de horas asignadas: Marzo 2027: 16 h.']);
});

it('tells why a group cannot be assigned', function (): void {
    app(AsignarDocente::class)($this->grupo->id, $this->docente->id, $this->gestor);

    cargaDe($this->docente, $this->gestor)
        ->callAction(TestAction::make('asignarGrupo')->table(), data: ['grupo_id' => $this->choque->id])
        ->assertNotified("El docente {$this->docente->codigo} ya dicta el grupo ING-02-A el Martes de 08:00 a 10:00, que se cruza con el grupo ING-02-B.");

    expect(CargaAcademica::query()->count())->toBe(1);
});

it('removes a group from the load', function (): void {
    $carga = app(AsignarDocente::class)($this->grupo->id, $this->docente->id, $this->gestor);

    cargaDe($this->docente, $this->gestor)
        ->callAction(TestAction::make('quitarGrupo')->table($carga))
        ->assertNotified('Grupo quitado de la carga del docente');

    expect(CargaAcademica::query()->exists())->toBeFalse();
});

it('shows the load to readers but does not let them change it', function (): void {
    $lector = User::factory()->create()->givePermissionTo([User::ADMIN_PANEL_PERMISSION, PermisoDocencia::Ver->value]);
    $carga = app(AsignarDocente::class)($this->grupo->id, $this->docente->id, $this->gestor);

    cargaDe($this->docente, $lector)
        ->assertSee('ING-02-A')
        ->assertActionHidden(TestAction::make('asignarGrupo')->table())
        ->assertActionHidden(TestAction::make('quitarGrupo')->table($carga))
        ->call('mountAction', 'quitarGrupo', [], ['table' => true, 'recordKey' => $carga->getKey()])
        ->call('callMountedAction')
        ->call('mountAction', 'asignarGrupo', [], ['table' => true])
        ->set('mountedActions.0.data', ['grupo_id' => $this->choque->id])
        ->call('callMountedAction');

    expect(CargaAcademica::query()->pluck('grupo_id')->all())->toBe([$this->grupo->id]);
});
