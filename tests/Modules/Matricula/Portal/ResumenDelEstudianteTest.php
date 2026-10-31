<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
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
