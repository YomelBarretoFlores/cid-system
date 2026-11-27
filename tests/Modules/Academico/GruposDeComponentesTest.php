<?php

declare(strict_types=1);

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Data\NotasCalculadas;
use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function migracionDeGruposDeComponentes(): Migration
{
    return require base_path('app/Modules/Academico/Database/Migrations/2026_11_17_100000_group_evaluation_components_into_skills_and_exams.php');
}

function migracionDeColumnasConfigurables(): Migration
{
    return require base_path('app/Modules/Academico/Database/Migrations/2026_12_05_100000_turn_evaluation_components_into_configurable_columns.php');
}

it('turns weights into the skills and exams groups by code, and back into equal weights that sum 100', function (): void {
    $esquema = EsquemaEvaluacion::factory()->create();
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'oral', 'orden' => 1]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'orden' => 2]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'orden' => 3]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->calculada(['oral', 'parcial', 'final'], ciclo: 1)->create(['codigo' => 'nota', 'orden' => 4]);
    // The test transaction never commits: checking now leaves no pending trigger event to block the ALTER TABLE.
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    migracionDeColumnasConfigurables()->down();

    migracionDeGruposDeComponentes()->down();

    expect(Schema::hasColumn('aca_componentes_evaluacion', 'grupo'))->toBeFalse()
        ->and(DB::table('aca_componentes_evaluacion')->orderBy('orden')->pluck('peso', 'codigo')->all())->toBe(['oral' => '33.33', 'parcial' => '33.33', 'final' => '33.34']);

    DB::table('aca_componentes_evaluacion')->where('codigo', 'oral')->update(['codigo' => 'participacion']);
    migracionDeGruposDeComponentes()->up();

    expect(Schema::hasColumn('aca_componentes_evaluacion', 'peso'))->toBeFalse()
        ->and(DB::table('aca_componentes_evaluacion')->orderBy('orden')->pluck('grupo', 'codigo')->all())->toBe(['participacion' => 'habilidades', 'parcial' => 'examenes', 'final' => 'examenes']);

    migracionDeColumnasConfigurables()->up();
});

it('turns the skills and exams of each scheme into entered columns plus the two averages and the final grade, and back', function (): void {
    $esquema = EsquemaEvaluacion::factory()->create();
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'oral', 'orden' => 1]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'escrita', 'orden' => 2]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'orden' => 3]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->calculada(['oral', 'escrita', 'parcial'], ciclo: 1)->create(['codigo' => 'nota', 'orden' => 4]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    migracionDeColumnasConfigurables()->down();

    migracionDeColumnasConfigurables()->up();

    expect(DB::table('aca_componentes_evaluacion')->where('esquema_id', $esquema->id)->orderBy('orden')->get(['codigo', 'tipo', 'calculo', 'redondeo', 'fuentes', 'ciclo', 'orden'])
        ->map(fn (object $fila): array => [$fila->codigo, $fila->tipo, $fila->calculo, $fila->redondeo, json_decode($fila->fuentes, true), $fila->ciclo, $fila->orden])->all())->toBe([
            ['oral', 'ingresada', null, 'ninguno', [], null, 1],
            ['escrita', 'ingresada', null, 'ninguno', [], null, 2],
            ['parcial', 'ingresada', null, 'ninguno', [], null, 3],
            ['promedio_habilidades', 'calculada', 'promedio', 'ninguno', [['peso' => 1, 'codigo' => 'oral'], ['peso' => 1, 'codigo' => 'escrita']], null, 4],
            ['promedio_examenes', 'calculada', 'promedio', 'ninguno', [['peso' => 1, 'codigo' => 'parcial']], null, 5],
            ['nota_final', 'calculada', 'promedio', 'entero', [['peso' => 1, 'codigo' => 'promedio_habilidades'], ['peso' => 1, 'codigo' => 'promedio_examenes']], 1, 6],
        ]);

    migracionDeColumnasConfigurables()->down();

    expect(DB::table('aca_componentes_evaluacion')->where('esquema_id', $esquema->id)->orderBy('orden')->pluck('grupo', 'codigo')->all())->toBe(['oral' => 'habilidades', 'escrita' => 'habilidades', 'parcial' => 'examenes']);

    migracionDeColumnasConfigurables()->up();
});

it('keeps a scheme that had only skills calculable, its final grade being the average of its skills', function (): void {
    $esquema = EsquemaEvaluacion::factory()->create();
    $oral = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'oral', 'orden' => 1]);
    $escrita = ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'escrita', 'orden' => 2]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->calculada(['oral', 'escrita'], ciclo: 1)->create(['codigo' => 'nota', 'orden' => 3]);
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');
    migracionDeColumnasConfigurables()->down();

    migracionDeColumnasConfigurables()->up();
    $columnas = $esquema->refresh()->componentes()->get()->map(fn (ComponenteEvaluacion $columna): ComponenteDeEvaluacion => $columna->aDatos())->all();

    expect(array_map(fn (ComponenteDeEvaluacion $c): string => $c->codigo, $columnas))->toBe(['oral', 'escrita', 'promedio_habilidades', 'nota_final'])
        ->and(NotasCalculadas::de($columnas, [$oral->id => '12.00', $escrita->id => '13.00'])->delCiclo(1))->toBe('13.00');
});
