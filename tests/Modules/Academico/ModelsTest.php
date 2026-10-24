<?php

declare(strict_types=1);

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use Illuminate\Database\Eloquent\MassAssignmentException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

it('identifies groups publicly by ULID', function (): void {
    $grupo = Grupo::factory()->create();

    expect(Str::isUlid($grupo->ulid))->toBeTrue()
        ->and($grupo->getRouteKeyName())->toBe('ulid')
        ->and($grupo->getRouteKey())->toBe($grupo->ulid);
});

it('reports available vacancies from capacity and enrolled students', function (): void {
    $grupo = Grupo::factory()->create(['cupo_maximo' => 25]);
    DB::table('aca_grupos')->where('id', $grupo->id)->update(['matriculados' => 7]);

    expect($grupo->refresh()->vacantes_disponibles)->toBe(18);
});

it('does not allow mass assignment of the enrolled counter', function (): void {
    $grupo = Grupo::factory()->create();

    expect(fn () => $grupo->fill(['matriculados' => 20]))->toThrow(MassAssignmentException::class)
        ->and($grupo->refresh()->matriculados)->toBe(0);
});

it('casts academic states and catalogs to enums', function (): void {
    $grupo = Grupo::factory()->create()->load('periodo', 'ciclo');

    expect($grupo->estado)->toBe(EstadoGrupo::Abierto)
        ->and($grupo->modalidad)->toBe(Modalidad::Presencial)
        ->and($grupo->periodo->estado)->toBe(EstadoPeriodo::Planificado)
        ->and($grupo->ciclo->nivel)->toBe(NivelCurricular::Basico);
});

it('starts new periods as planned and new groups as scheduled with no students', function (): void {
    $periodo = Periodo::factory()->create();
    $grupo = Grupo::query()->create([
        'periodo_id' => $periodo->id,
        'ciclo_id' => Ciclo::factory()->create()->id,
        'codigo' => 'ING-B1-01',
        'modalidad' => Modalidad::Virtual,
        'cupo_maximo' => 20,
        'cupo_minimo' => 8,
    ]);

    expect($periodo->refresh()->estado)->toBe(EstadoPeriodo::Planificado)
        ->and($grupo->refresh()->estado)->toBe(EstadoGrupo::Programado)
        ->and($grupo->matriculados)->toBe(0)
        ->and($grupo->es_acelerado)->toBeFalse();
});

it('lists group schedules by day and start time', function (): void {
    $grupo = Grupo::factory()->create();
    Horario::factory()->for($grupo)->create(['dia_semana' => 3, 'hora_inicio' => '08:00', 'hora_fin' => '10:00']);
    Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    Horario::factory()->for($grupo)->create(['dia_semana' => 1, 'hora_inicio' => '07:00', 'hora_fin' => '09:00']);

    expect($grupo->horarios->map(fn (Horario $h): string => "{$h->dia_semana} {$h->hora_inicio}")->all())
        ->toBe(['1 07:00:00', '1 18:00:00', '3 08:00:00']);
});

it('lists evaluation components in their configured order', function (): void {
    $esquema = EsquemaEvaluacion::factory()->create();
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'peso' => '40.00', 'orden' => 2]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'peso' => '40.00', 'orden' => 1]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'participacion', 'peso' => '20.00', 'orden' => 3]);

    expect($esquema->componentes->pluck('codigo')->all())->toBe(['parcial', 'final', 'participacion'])
        ->and($esquema->componentes->sum('peso'))->toEqual(100);
});
