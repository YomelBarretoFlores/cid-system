<?php

declare(strict_types=1);

use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

function migracionDeGruposDeComponentes(): Migration
{
    return require base_path('app/Modules/Academico/Database/Migrations/2026_11_17_100000_group_evaluation_components_into_skills_and_exams.php');
}

it('turns weights into the skills and exams groups by code, and back into equal weights that sum 100', function (): void {
    $esquema = EsquemaEvaluacion::factory()->create();
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'oral', 'orden' => 1]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'parcial', 'orden' => 2]);
    ComponenteEvaluacion::factory()->for($esquema, 'esquema')->create(['codigo' => 'final', 'orden' => 3]);
    // The test transaction never commits: checking now leaves no pending trigger event to block the ALTER TABLE.
    DB::statement('SET CONSTRAINTS ALL IMMEDIATE');

    migracionDeGruposDeComponentes()->down();

    expect(Schema::hasColumn('aca_componentes_evaluacion', 'grupo'))->toBeFalse()
        ->and(DB::table('aca_componentes_evaluacion')->orderBy('orden')->pluck('peso', 'codigo')->all())->toBe(['oral' => '33.33', 'parcial' => '33.33', 'final' => '33.34']);

    DB::table('aca_componentes_evaluacion')->where('codigo', 'oral')->update(['codigo' => 'participacion']);
    migracionDeGruposDeComponentes()->up();

    expect(Schema::hasColumn('aca_componentes_evaluacion', 'peso'))->toBeFalse()
        ->and(DB::table('aca_componentes_evaluacion')->orderBy('orden')->pluck('grupo', 'codigo')->all())->toBe(['participacion' => 'habilidades', 'parcial' => 'examenes', 'final' => 'examenes']);
});
