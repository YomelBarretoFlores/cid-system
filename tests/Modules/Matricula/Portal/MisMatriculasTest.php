<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

it('lists only the own enrollments, newest first, with group detail, state, condition and date in Lima', function (): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id]);
    $actual = Grupo::factory()->create(['codigo' => 'ING-II-A']);
    Horario::factory()->for($actual)->create(['dia_semana' => 2, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    Matricula::factory()->create([
        'persona_id' => $persona->id, 'grupo_id' => $actual->id, 'condicion' => CondicionMatricula::Repitente, 'matriculado_en' => '2026-10-20 15:30:00+00',
    ]);
    Matricula::factory()->retirada()->create([
        'persona_id' => $persona->id, 'grupo_id' => Grupo::factory()->create(['codigo' => 'ING-I-A'])->id, 'matriculado_en' => '2026-03-02 13:00:00+00',
    ]);
    Matricula::factory()->create(['grupo_id' => Grupo::factory()->create(['codigo' => 'AJENO-1'])->id]);

    $this->actingAs($user)
        ->get(route('estudiante.matriculas'))
        ->assertOk()
        ->assertSeeInOrder(['ING-II-A', 'Martes de 18:00 a 20:00', 'Activa', 'Repitente', '20/10/2026 10:30', 'ING-I-A', 'Retirada', 'Regular', '02/03/2026 08:00'])
        ->assertDontSee('AJENO-1');
});

it('tells a student without enrollments where to enroll', function (): void {
    $user = User::factory()->create();
    Persona::factory()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->get(route('estudiante.matriculas'))
        ->assertOk()
        ->assertSee('Aún no tienes matrículas.')
        ->assertSee(route('estudiante.matricula'));
});
