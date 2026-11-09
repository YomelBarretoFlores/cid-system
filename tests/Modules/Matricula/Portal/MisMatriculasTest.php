<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;

it('lists only the own enrollments in a table per period, newest first, with group detail, condition, state, date in Lima and documents', function (): void {
    $user = User::factory()->create();
    $persona = Persona::factory()->create(['user_id' => $user->id]);
    $actual = Grupo::factory()->create(['codigo' => 'ING-II-A']);
    Horario::factory()->for($actual)->create(['dia_semana' => 2, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    $activa = Matricula::factory()->create([
        'persona_id' => $persona->id, 'grupo_id' => $actual->id, 'condicion' => CondicionMatricula::Repitente, 'matriculado_en' => '2026-10-20 15:30:00+00',
    ]);
    Matricula::factory()->retirada()->create([
        'persona_id' => $persona->id, 'grupo_id' => Grupo::factory()->create(['codigo' => 'ING-I-A'])->id, 'matriculado_en' => '2026-03-02 13:00:00+00',
    ]);
    Matricula::factory()->create(['grupo_id' => Grupo::factory()->create(['codigo' => 'AJENO-1'])->id]);

    $this->actingAs($user)
        ->get(route('estudiante.matriculas'))
        ->assertOk()
        ->assertSee('<table class="tabla-portal">', false)
        ->assertSeeInOrder(['Idioma', 'Ciclo', 'Grupo', 'Horario', 'Aula', 'Docente', 'Condición', 'Estado', 'Matriculado el', 'Documentos'])
        ->assertSeeInOrder(['ING-II-A', 'Mar, 18:00–20:00', 'Repitente', 'Activa', '20/10/2026 10:30', route('estudiante.matriculas.constancia', $activa), 'ING-I-A', 'Regular', 'Retirada', '02/03/2026 08:00'])
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
