<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Grupo;
use App\Modules\Matricula\Contracts\EstudiantesDelGrupo;
use App\Modules\Matricula\Data\EstudianteDelGrupo;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;

it('lists the active enrollments of a group in alphabetical order of surnames', function (): void {
    $grupo = Grupo::factory()->create();
    $quispe = Matricula::factory()->create(['grupo_id' => $grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Quispe', 'apellido_materno' => 'Rojas', 'nombres' => 'Ana'])->id]);
    $alvarez = Matricula::factory()->create(['grupo_id' => $grupo->id, 'persona_id' => Persona::factory()->create(['apellido_paterno' => 'Álvarez', 'apellido_materno' => 'Mejía', 'nombres' => 'Luis'])->id]);
    Matricula::factory()->retirada()->create(['grupo_id' => $grupo->id]);
    Matricula::factory()->create();

    $estudiantes = app(EstudiantesDelGrupo::class)($grupo->id);

    expect($estudiantes)->toEqual([
        new EstudianteDelGrupo($alvarez->id, $alvarez->persona_id, 'Álvarez Mejía, Luis'),
        new EstudianteDelGrupo($quispe->id, $quispe->persona_id, 'Quispe Rojas, Ana'),
    ]);
});

it('returns an empty list for a group without active enrollments', function (): void {
    expect(app(EstudiantesDelGrupo::class)(Grupo::factory()->create()->id))->toBe([]);
});
