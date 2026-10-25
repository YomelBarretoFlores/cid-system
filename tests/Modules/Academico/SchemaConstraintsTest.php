<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

it('rejects rows that break an academic invariant', function (Closure $violation, string $constraint): void {
    expect($violation)->toThrow(QueryException::class, $constraint);
})->with([
    'cycle number above XII' => [fn () => Ciclo::factory()->create(['numero' => 13]), 'aca_ciclos_numero_check'],
    'cycle without lecture hours' => [fn () => Ciclo::factory()->create(['horas_lectivas' => 0]), 'aca_ciclos_horas_lectivas_check'],
    'period ending before it starts' => [fn () => Periodo::factory()->create(['fecha_fin' => '2027-02-01']), 'aca_periodos_fechas_check'],
    'enrollment closing after the period ends' => [fn () => Periodo::factory()->create(['matricula_fin' => '2027-04-15']), 'aca_periodos_matricula_check'],
    'grade calendar with a start only' => [fn () => Periodo::factory()->create(['actas_inicio' => '2027-03-29']), 'aca_periodos_actas_check'],
    'grade calendar ending before it starts' => [fn () => Periodo::factory()->create(['actas_inicio' => '2027-03-29', 'actas_fin' => '2027-03-28']), 'aca_periodos_actas_check'],
    'classroom without capacity' => [fn () => Aula::factory()->create(['capacidad' => 0]), 'aca_aulas_capacidad_check'],
    'minimum quota above maximum' => [fn () => Grupo::factory()->create(['cupo_minimo' => 30, 'cupo_maximo' => 25]), 'aca_grupos_cupos_check'],
    'negative minimum quota' => [fn () => Grupo::factory()->create(['cupo_minimo' => -1]), 'aca_grupos_cupos_check'],
    'unknown modality' => [fn () => DB::table('aca_grupos')->where('id', Grupo::factory()->create()->id)->update(['modalidad' => 'hibrida']), 'aca_grupos_modalidad_check'],
    'schedule ending before it starts' => [fn () => Horario::factory()->create(['hora_inicio' => '10:00', 'hora_fin' => '09:00']), 'aca_horarios_horas_check'],
    'day of week outside 1-7' => [fn () => Horario::factory()->create(['dia_semana' => 8]), 'aca_horarios_dia_semana_check'],
    'passing grade above the maximum' => [fn () => EsquemaEvaluacion::factory()->create(['nota_minima_aprobatoria' => '21.00']), 'aca_esquemas_evaluacion_notas_check'],
    'validity ending before it starts' => [fn () => EsquemaEvaluacion::factory()->create(['vigente_hasta' => '2026-12-31']), 'aca_esquemas_evaluacion_vigencia_check'],
    'component weight above 100' => [fn () => ComponenteEvaluacion::factory()->create(['peso' => '100.01']), 'aca_componentes_evaluacion_peso_check'],
]);

it('never lets enrolled students exceed the group capacity', function (): void {
    $grupo = Grupo::factory()->create(['cupo_maximo' => 25]);

    expect(fn () => DB::table('aca_grupos')->where('id', $grupo->id)->update(['matriculados' => 26]))
        ->toThrow(QueryException::class, 'aca_grupos_matriculados_check');
});

it('never lets the enrolled counter go negative', function (): void {
    $grupo = Grupo::factory()->create();

    expect(fn () => DB::table('aca_grupos')->where('id', $grupo->id)->decrement('matriculados'))
        ->toThrow(QueryException::class, 'aca_grupos_matriculados_check');
});

it('keeps cycle numbers unique per language', function (): void {
    $ciclo = Ciclo::factory()->create(['numero' => 1]);

    expect(fn () => Ciclo::factory()->create(['idioma_id' => $ciclo->idioma_id, 'numero' => 1]))
        ->toThrow(QueryException::class, 'aca_ciclos_idioma_id_numero_unique');
});

it('keeps group codes unique per period but reusable across periods', function (): void {
    $grupo = Grupo::factory()->create(['codigo' => 'ING-01']);

    expect(Grupo::factory()->create(['codigo' => 'ING-01'])->exists)->toBeTrue()
        ->and(fn () => Grupo::factory()->create(['periodo_id' => $grupo->periodo_id, 'codigo' => 'ING-01']))
        ->toThrow(QueryException::class, 'aca_grupos_periodo_id_codigo_unique');
});

it('refuses to delete a language that still has cycles', function (): void {
    $ciclo = Ciclo::factory()->create();

    expect(fn () => Idioma::query()->whereKey($ciclo->idioma_id)->delete())
        ->toThrow(QueryException::class, 'aca_ciclos_idioma_id_foreign');
});

it('removes the schedule together with its group', function (): void {
    $horario = Horario::factory()->create();

    Grupo::query()->whereKey($horario->grupo_id)->delete();

    expect(Horario::query()->whereKey($horario->id)->exists())->toBeFalse();
});
