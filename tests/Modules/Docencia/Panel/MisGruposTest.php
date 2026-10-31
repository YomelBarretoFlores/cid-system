<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    [$this->usuario, $this->docente] = PortalDocente::docente();
    $this->ciclo = Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->create(['numero' => 5, 'nivel' => NivelCurricular::Intermedio]);
    $this->periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027']);
});

function grupoDelPortal(string $codigo, EstadoGrupo $estado = EstadoGrupo::Abierto): Grupo
{
    return Grupo::factory()->create(['codigo' => $codigo, 'ciclo_id' => test()->ciclo->id, 'periodo_id' => test()->periodo->id, 'estado' => $estado]);
}

it('lists the current groups of the teacher with language, level, period and schedule', function (): void {
    $grupo = grupoDelPortal('ING-05-A');
    Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    PortalDocente::asignar($grupo, $this->docente);
    PortalDocente::asignar(grupoDelPortal('ING-05-CANCELADO', EstadoGrupo::Cancelado), $this->docente);
    PortalDocente::asignar(grupoDelPortal('ING-05-FINALIZADO', EstadoGrupo::Finalizado), $this->docente);
    PortalDocente::asignar(grupoDelPortal('ING-05-AJENO'), Docente::factory()->create());

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertOk()
        ->assertSeeInOrder(['Mis grupos', 'Grupo ING-05-A', 'Inglés · Ciclo 5 · Intermedio', 'Marzo 2027', 'Lun, 18:00–20:00'])
        ->assertDontSee('ING-05-CANCELADO')
        ->assertDontSee('ING-05-FINALIZADO')
        ->assertDontSee('ING-05-AJENO')
        ->assertSee(["/docente/grupos/{$grupo->id}/asistencia", "/docente/grupos/{$grupo->id}/notas", "/docente/grupos/{$grupo->id}/acta"]);
});

it('lists the groups of the latest period first, then by code', function (): void {
    $anterior = Periodo::factory()->create(['anio' => 2026, 'fecha_inicio' => '2026-09-01', 'fecha_fin' => '2026-10-30', 'matricula_inicio' => '2026-08-15', 'matricula_fin' => '2026-09-05']);
    PortalDocente::asignar(Grupo::factory()->create(['codigo' => 'ING-05-Z', 'ciclo_id' => $this->ciclo->id, 'periodo_id' => $anterior->id, 'estado' => EstadoGrupo::Cerrado]), $this->docente);
    PortalDocente::asignar(grupoDelPortal('ING-05-C'), $this->docente);
    PortalDocente::asignar(grupoDelPortal('ING-05-B'), $this->docente);

    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertSeeInOrder(['Grupo ING-05-B', 'Grupo ING-05-C', 'Grupo ING-05-Z']);
});

it('tells a teacher without groups that none is assigned yet', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('filament.docente.pages.mis-grupos'))
        ->assertOk()
        ->assertSee('Aún no tienes grupos asignados.');
});

it('shows the teacher portal on the home only to teachers', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Registra la asistencia y las notas parciales de tus grupos.');

    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id]);

    $this->actingAs($estudiante)
        ->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertDontSee(route('filament.docente.pages.mis-grupos'))
        ->assertDontSee('Registra la asistencia y las notas parciales de tus grupos.')
        ->assertSee(route('estudiante.matriculas'));
});

it('links the teacher portal from the header of every portal page only for teachers', function (): void {
    $this->actingAs($this->usuario)->get(route('estudiante.matriculas'))->assertSee(route('filament.docente.pages.mis-grupos'));

    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id]);

    $this->actingAs($estudiante)->get(route('estudiante.matriculas'))->assertOk()->assertDontSee(route('filament.docente.pages.mis-grupos'));
});
