<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Matricula\Contracts\DocumentoDeMatriculaPorId;
use App\Modules\Matricula\Contracts\MatriculasActivasDePersona;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;

beforeEach(function (): void {
    $this->persona = Persona::factory()->create(['apellido_paterno' => 'Alva', 'apellido_materno' => 'Prueba', 'nombres' => 'Ana']);
    $ciclo = Ciclo::factory()->for(Idioma::factory()->state(['nombre' => 'Inglés']))->create(['numero' => 2]);
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-2-M', 'ciclo_id' => $ciclo->id, 'periodo_id' => Periodo::factory()->create(['nombre' => 'Marzo 2027'])->id]);
});

it('lists the active enrollments of a person with group, language, cycle and period, newest first', function (): void {
    $anterior = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id, 'matriculado_en' => '2027-01-10 10:00']);
    $actual = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id, 'grupo_id' => $this->grupo->id, 'matriculado_en' => '2027-02-20 10:00']);
    Matricula::factory()->retirada()->create(['persona_id' => $this->persona->id]);
    Matricula::factory()->activa()->create();

    $matriculas = app(MatriculasActivasDePersona::class)($this->persona->id);

    expect(array_keys($matriculas))->toBe([$actual->id, $anterior->id])
        ->and($matriculas[$actual->id])->toBe('ING-2-M · Inglés ciclo 2 · Marzo 2027');
});

it('gives the data of one enrollment for a record, or nothing when it does not exist', function (): void {
    $matricula = Matricula::factory()->activa()->create(['persona_id' => $this->persona->id, 'grupo_id' => $this->grupo->id]);

    $documento = app(DocumentoDeMatriculaPorId::class)($matricula->id);

    expect($documento?->codigo)->toBe($matricula->ulid)
        ->and($documento?->estudiante->nombreCompleto)->toBe('Alva Prueba, Ana')
        ->and($documento?->grupo->codigo)->toBe('ING-2-M')
        ->and($documento?->estado)->toBe(EstadoMatricula::Activa)
        ->and(app(DocumentoDeMatriculaPorId::class)(999_999))->toBeNull();
});
