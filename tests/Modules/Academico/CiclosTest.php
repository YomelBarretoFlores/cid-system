<?php

declare(strict_types=1);

use App\Modules\Academico\Actions\GuardarCiclo;
use App\Modules\Academico\Data\DatosDeCiclo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Shared\Support\Exceptions\ReglaDeNegocioIncumplida;

function datosDeCiclo(array $cambios = []): DatosDeCiclo
{
    return new DatosDeCiclo(...[
        'numero' => 1,
        'nivel' => NivelCurricular::Basico,
        'nombre' => 'Básico I',
        'horasLectivas' => 60,
        'activo' => true,
        ...$cambios,
    ]);
}

beforeEach(function (): void {
    $this->idioma = Idioma::factory()->create();
    $this->ciclo = Ciclo::factory()->for($this->idioma)->create(['numero' => 1, 'nivel' => NivelCurricular::Basico, 'nombre' => 'Básico I', 'horas_lectivas' => 60]);
});

it('creates a cycle of the language through the action', function (): void {
    $ciclo = app(GuardarCiclo::class)($this->idioma->id, datosDeCiclo(['numero' => 2, 'nombre' => 'Básico II', 'horasLectivas' => 64]));

    expect($ciclo->refresh())
        ->idioma_id->toBe($this->idioma->id)
        ->numero->toBe(2)
        ->nivel->toBe(NivelCurricular::Basico)
        ->horas_lectivas->toBe(64)
        ->activo->toBeTrue();
});

it('refuses to change the number, level or teaching hours of a cycle that already has groups', function (array $cambio): void {
    Grupo::factory()->create(['ciclo_id' => $this->ciclo->id]);

    expect(fn (): Ciclo => app(GuardarCiclo::class)($this->idioma->id, datosDeCiclo($cambio), $this->ciclo->id))
        ->toThrow(ReglaDeNegocioIncumplida::class, 'El ciclo Básico I ya tiene grupos: su número, nivel y horas lectivas no pueden cambiar.');

    expect($this->ciclo->refresh())
        ->numero->toBe(1)
        ->nivel->toBe(NivelCurricular::Basico)
        ->horas_lectivas->toBe(60);
})->with([
    'number' => [['numero' => 2]],
    'level' => [['nivel' => NivelCurricular::Intermedio]],
    'teaching hours' => [['horasLectivas' => 72]],
]);

it('still renames and deactivates a cycle that has groups', function (): void {
    Grupo::factory()->create(['ciclo_id' => $this->ciclo->id]);

    app(GuardarCiclo::class)($this->idioma->id, datosDeCiclo(['nombre' => 'Básico I (2027)', 'activo' => false]), $this->ciclo->id);

    expect($this->ciclo->refresh())
        ->nombre->toBe('Básico I (2027)')
        ->activo->toBeFalse()
        ->numero->toBe(1);
});

it('changes the number, level and teaching hours of a cycle without groups', function (): void {
    app(GuardarCiclo::class)($this->idioma->id, datosDeCiclo(['numero' => 3, 'nivel' => NivelCurricular::Intermedio, 'horasLectivas' => 72]), $this->ciclo->id);

    expect($this->ciclo->refresh())
        ->numero->toBe(3)
        ->nivel->toBe(NivelCurricular::Intermedio)
        ->horas_lectivas->toBe(72);
});
