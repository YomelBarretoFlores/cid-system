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
        ->get(route('docente.grupos'))
        ->assertOk()
        ->assertSeeInOrder(['Mis grupos', 'Grupo ING-05-A', 'Inglés · Ciclo 5 · Intermedio', 'Marzo 2027', 'Lunes de 18:00 a 20:00'])
        ->assertDontSee('ING-05-CANCELADO')
        ->assertDontSee('ING-05-FINALIZADO')
        ->assertDontSee('ING-05-AJENO');
});

it('tells a teacher without groups that none is assigned yet', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('docente.grupos'))
        ->assertOk()
        ->assertSee('Aún no tienes grupos asignados.');
});

it('refuses a user whose person has no teacher profile', function (): void {
    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id]);

    $this->actingAs($estudiante)
        ->get(route('docente.grupos'))
        ->assertForbidden()
        ->assertSee('Tu cuenta no está vinculada a un perfil docente del CILN.');
});

it('sends a guest to the portal login', function (): void {
    $this->get(route('docente.grupos'))->assertRedirect(route('estudiante.ingresar'));
});

it('shows the teacher portal in the header and the home only to teachers', function (): void {
    $this->actingAs($this->usuario)
        ->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee(route('docente.grupos'))
        ->assertSee('Registra la asistencia y las notas parciales de tus grupos.');

    $estudiante = User::factory()->create();
    Persona::factory()->create(['user_id' => $estudiante->id]);

    $this->actingAs($estudiante)
        ->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertDontSee(route('docente.grupos'))
        ->assertSee(route('estudiante.matriculas'));
});
