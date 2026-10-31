<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Carbon\CarbonImmutable;
use Tests\Support\PortalDocente;

beforeEach(function (): void {
    $this->user = User::factory()->create();
    $this->persona = Persona::factory()->create(['user_id' => $this->user->id, 'nombres' => 'Lucía']);
});

it('asks a student without a usable payment to register the enrollment payment', function (): void {
    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Hola, Lucía.', 'Registra tu pago de matrícula.', 'Registrar pago', 'Aún no tienes clases.', 'Aún no estudias ningún idioma en el CILN.']);
});

it('shows an enrolled student the group, the weekly classes and the cycle in progress', function (): void {
    $grupo = Grupo::factory()
        ->for(Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Inglés']))->state(['numero' => 2]))
        ->create(['aula_id' => Aula::factory()->create(['codigo' => 'A-204'])->id]);
    Horario::factory()->for($grupo)->create(['dia_semana' => 2, 'hora_inicio' => '07:00', 'hora_fin' => '08:30']);
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $grupo->id]);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSeeInOrder(['Ya estás matriculado en Inglés · Ciclo 2.', 'Ver mis matrículas', 'Mar', '07:00', 'A-204', 'Cursando el ciclo 2']);
});

it('points a teacher without any enrollment to the groups instead of asking for an enrollment payment', function (): void {
    [$docente] = PortalDocente::docente();

    $this->actingAs($docente)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Tus grupos están en Mis grupos.')
        ->assertSee(route('filament.docente.pages.mis-grupos'))
        ->assertDontSee('Registra tu pago de matrícula.')
        ->assertDontSee('Tu horario semanal');
});

it('treats an enrollment whose period already ended as a finished cycle, not as current classes', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-03-10 09:00', 'America/Lima'));
    $pasado = Periodo::factory()->create(['nombre' => 'Enero 2027', 'fecha_inicio' => '2027-01-04', 'fecha_fin' => '2027-01-31', 'matricula_inicio' => '2026-12-20', 'matricula_fin' => '2027-01-03']);
    $grupo = Grupo::factory()->for($pasado)
        ->for(Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Portugués']))->state(['numero' => 2]))
        ->create(['aula_id' => Aula::factory()->create(['codigo' => 'P-301'])->id]);
    Horario::factory()->for($grupo)->create(['dia_semana' => 2, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $grupo->id]);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Registra tu pago de matrícula.')
        ->assertSee('Aún no tienes clases.')
        ->assertSee('Terminó el ciclo 2 en Enero 2027')
        ->assertDontSee('P-301')
        ->assertDontSee('Cursando el ciclo 2');
});

it('keeps an enrollment current on the last day of its period, in Lima', function (): void {
    $this->travelTo(CarbonImmutable::parse('2027-01-31 18:00', 'America/Lima'));
    $periodo = Periodo::factory()->create(['nombre' => 'Enero 2027', 'fecha_inicio' => '2027-01-04', 'fecha_fin' => '2027-01-31', 'matricula_inicio' => '2026-12-20', 'matricula_fin' => '2027-01-03']);
    $grupo = Grupo::factory()->for($periodo)->for(Ciclo::factory()->for(Idioma::factory()->create(['nombre' => 'Portugués']))->state(['numero' => 2]))->create();
    Matricula::factory()->create(['persona_id' => $this->persona->id, 'grupo_id' => $grupo->id]);

    $this->actingAs($this->user)->get(route('estudiante.inicio'))
        ->assertOk()
        ->assertSee('Ya estás matriculado en Portugués · Ciclo 2.')
        ->assertSee('Cursando el ciclo 2');
});
