<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Docencia\Models\CargaAcademica;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Tests\Support\PortalDocente;

// March runs 1–31, the bimester 15 March–15 May overlaps it, and April 1–30 overlaps only the bimester.
beforeEach(function (): void {
    $this->marzo = Periodo::factory()->create(['numero' => 3, 'fecha_inicio' => '2027-03-01', 'fecha_fin' => '2027-03-31']);
    $this->bimestre = Periodo::factory()->create(['numero' => 5, 'fecha_inicio' => '2027-03-15', 'fecha_fin' => '2027-05-15', 'matricula_fin' => '2027-03-15']);
    $this->abril = Periodo::factory()->create(['numero' => 4, 'fecha_inicio' => '2027-04-01', 'fecha_fin' => '2027-04-30', 'matricula_inicio' => '2027-03-20', 'matricula_fin' => '2027-04-05']);
    $this->aula = Aula::factory()->create(['codigo' => 'A-101', 'capacidad' => 30]);
    $ocupante = Grupo::factory()->create(['periodo_id' => $this->marzo->id, 'aula_id' => $this->aula->id, 'codigo' => 'FRA-01']);
    Horario::factory()->for($ocupante)->create(['dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);
});

function grupoConAulaA101(Periodo $periodo, string $codigo = 'ING-01'): Grupo
{
    return Grupo::factory()->create(['periodo_id' => $periodo->id, 'aula_id' => test()->aula->id, 'codigo' => $codigo]);
}

it('lets a direct write that causes no clash through', function (): void {
    $grupo = grupoConAulaA101($this->abril);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    DB::table('aca_horarios')->insert(['grupo_id' => $grupo->id, 'dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('aca_periodos')->where('id', $this->abril->id)->update(['fecha_fin' => '2027-05-10']);

    expect($grupo->horarios()->count())->toBe(1);
});

it('makes the database refuse a classroom clash written outside the Actions', function (Closure $romper): void {
    $grupo = grupoConAulaA101($this->abril);
    Horario::factory()->for($grupo)->create(['dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00']);

    // The guard runs at commit; the tests run inside one transaction, so they ask for the check right away.
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(fn () => DB::transaction(fn (): mixed => $romper->call($this, $grupo)))->toThrow(QueryException::class, 'aula A-101 ocupada');
})->with([
    'a schedule line' => [fn (Grupo $grupo) => DB::table('aca_horarios')->insert(['grupo_id' => grupoConAulaA101($this->bimestre, 'ING-09')->id, 'dia_semana' => 5, 'hora_inicio' => '17:00', 'hora_fin' => '18:30', 'created_at' => now(), 'updated_at' => now()])],
    'a group moved to another period' => [fn (Grupo $grupo) => DB::table('aca_grupos')->where('id', $grupo->id)->update(['periodo_id' => $this->bimestre->id])],
    'new period dates' => [fn (Grupo $grupo) => DB::table('aca_periodos')->where('id', $this->abril->id)->update(['fecha_inicio' => '2027-03-25', 'matricula_inicio' => '2027-03-10', 'matricula_fin' => '2027-03-20'])],
]);

it('makes the database refuse a teacher clash written outside the Actions', function (): void {
    [, $docente] = PortalDocente::docente();
    $uno = Grupo::factory()->create(['periodo_id' => $this->marzo->id]);
    Horario::factory()->for($uno)->create(['dia_semana' => 2, 'hora_inicio' => '18:00', 'hora_fin' => '20:00']);
    $dos = Grupo::factory()->create(['periodo_id' => $this->bimestre->id]);
    Horario::factory()->for($dos)->create(['dia_semana' => 2, 'hora_inicio' => '19:00', 'hora_fin' => '21:00']);
    PortalDocente::asignar($uno, $docente);

    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    expect(fn () => DB::transaction(fn (): CargaAcademica => PortalDocente::asignar($dos, $docente)))->toThrow(QueryException::class, "docente {$docente->codigo} con dos grupos a la misma hora");
});

it('keeps working with a classroom shared across overlapping periods before this rule existed, while refusing a new clash', function (): void {
    // Saved before the rule: the guard only judges writes that can create a clash.
    $legado = grupoConAulaA101($this->bimestre, 'ING-L');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    DB::statement('ALTER TABLE aca_horarios DISABLE TRIGGER aca_horarios_aula_libre');
    DB::table('aca_horarios')->insert(['grupo_id' => $legado->id, 'dia_semana' => 5, 'hora_inicio' => '16:00', 'hora_fin' => '18:00', 'created_at' => now(), 'updated_at' => now()]);
    DB::statement('ALTER TABLE aca_horarios ENABLE TRIGGER aca_horarios_aula_libre');
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    DB::table('aca_grupos')->where('id', $legado->id)->update(['estado' => 'abierto']);
    DB::table('aca_horarios')->insert(['grupo_id' => $legado->id, 'dia_semana' => 1, 'hora_inicio' => '08:00', 'hora_fin' => '10:00', 'created_at' => now(), 'updated_at' => now()]);
    DB::table('aca_periodos')->where('id', $this->bimestre->id)->update(['fecha_fin' => '2027-05-10']);

    expect($legado->horarios()->count())->toBe(2)
        ->and(fn () => DB::transaction(fn (): bool => DB::table('aca_periodos')->where('id', $this->bimestre->id)->update(['fecha_fin' => '2027-06-30']) > 0))->not->toThrow(QueryException::class)
        ->and(fn () => DB::transaction(fn (): bool => DB::table('aca_horarios')->insert(['grupo_id' => $legado->id, 'dia_semana' => 5, 'hora_inicio' => '17:30', 'hora_fin' => '19:00', 'created_at' => now(), 'updated_at' => now()])))->toThrow(QueryException::class, 'aula A-101 ocupada');
});
