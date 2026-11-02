<?php

declare(strict_types=1);

use App\Modules\Academico\Models\Aula;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Academico\Models\Periodo;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

it('records every insert, update and delete of the people and academic offer tables', function (string $modelo): void {
    /** @var Model $registro */
    $registro = $modelo::factory()->create();
    $tabla = $registro->getTable();
    DB::table($tabla)->where('id', $registro->getKey())->update(['updated_at' => now()->addMinute()]);
    DB::table($tabla)->where('id', $registro->getKey())->delete();

    $operaciones = DB::table('aud_registros')
        ->where(['tabla' => $tabla, 'registro_id' => $registro->getKey()])
        ->orderBy('id')
        ->pluck('operacion')
        ->all();

    expect($operaciones)->toBe(['INSERT', 'UPDATE', 'DELETE']);
})->with([
    'per_personas' => Persona::class,
    'aca_periodos' => Periodo::class,
    'aca_grupos' => Grupo::class,
    'aca_horarios' => Horario::class,
    'aca_aulas' => Aula::class,
    'aca_idiomas' => Idioma::class,
    'aca_ciclos' => Ciclo::class,
]);
