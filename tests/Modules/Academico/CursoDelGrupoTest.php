<?php

declare(strict_types=1);

use App\Modules\Academico\Contracts\CursoDelGrupo;
use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use Carbon\CarbonImmutable;

beforeEach(function (): void {
    $ciclo = Ciclo::factory()->for(Idioma::factory()->state(['nombre' => 'Inglés']))->create(['numero' => 6, 'nivel' => NivelCurricular::Intermedio, 'nombre' => 'Intermedio II', 'horas_lectivas' => 60]);
    $periodo = Periodo::factory()->create(['nombre' => 'Marzo 2027', 'actas_inicio' => '2027-03-29', 'actas_fin' => '2027-04-05']);
    $this->grupo = Grupo::factory()->create(['codigo' => 'ING-6-M', 'ciclo_id' => $ciclo->id, 'periodo_id' => $periodo->id, 'estado' => EstadoGrupo::Cerrado]);
});

it('gives the course data of a group with its period grade calendar', function (): void {
    $curso = app(CursoDelGrupo::class)($this->grupo->id);

    expect($curso)
        ->grupoId->toBe($this->grupo->id)
        ->codigo->toBe('ING-6-M')
        ->estado->toBe(EstadoGrupo::Cerrado)
        ->idioma->toBe('Inglés')
        ->nivel->toBe(NivelCurricular::Intermedio)
        ->cicloNumero->toBe(6)
        ->cicloNombre->toBe('Intermedio II')
        ->horasLectivas->toBe(60)
        ->periodoId->toBe($this->grupo->periodo_id)
        ->periodo->toBe('Marzo 2027')
        ->and($curso->actasInicio?->toDateString())->toBe('2027-03-29')
        ->and($curso->actasFin?->toDateString())->toBe('2027-04-05');
});

it('admits final grade sheets only on the days of the grade calendar', function (string $dia, bool $admite): void {
    expect(app(CursoDelGrupo::class)($this->grupo->id)->admiteActasEl(CarbonImmutable::parse($dia, 'America/Lima')))->toBe($admite);
})->with([
    'the day before' => ['2027-03-28 23:59', false],
    'the first day' => ['2027-03-29 00:00', true],
    'the last day' => ['2027-04-05 23:59', true],
    'the day after' => ['2027-04-06 00:00', false],
]);

it('reads the grade calendar in Lima time whatever the time zone of the moment', function (string $momento, bool $admite): void {
    expect(app(CursoDelGrupo::class)($this->grupo->id)->admiteActasEl(CarbonImmutable::parse($momento, 'UTC')))->toBe($admite);
})->with([
    'late on the eve in Lima, already the first day in UTC' => ['2027-03-29 04:59', false],
    'the first day in Lima' => ['2027-03-29 05:00', true],
    'late on the last day in Lima, already the next day in UTC' => ['2027-04-06 04:59', true],
]);

it('admits no final grade sheet when the period has no grade calendar', function (): void {
    Periodo::query()->whereKey($this->grupo->periodo_id)->update(['actas_inicio' => null, 'actas_fin' => null]);

    $curso = app(CursoDelGrupo::class)($this->grupo->id);

    expect($curso->actasInicio)->toBeNull()
        ->and($curso->admiteActasEl(CarbonImmutable::parse('2027-03-30', 'America/Lima')))->toBeFalse();
});

it('gives nothing for an unknown group', function (): void {
    expect(app(CursoDelGrupo::class)(999_999))->toBeNull();
});
