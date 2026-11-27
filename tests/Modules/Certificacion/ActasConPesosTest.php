<?php

declare(strict_types=1);

use App\Modules\Academico\Data\ComponenteDeEvaluacion;
use App\Modules\Academico\Enums\RedondeoDeNota;
use App\Modules\Academico\Enums\TipoDeComponente;
use App\Modules\Certificacion\Models\Acta;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Tests\Support\ActasFinales;

function migracionDeColumnasDelActa(): Migration
{
    return require base_path('app/Modules/Certificacion/Database/Migrations/2026_12_05_100100_describe_acta_columns_with_their_calculation.php');
}

it('describes the columns of an acta closed with groups or weights in the new shape, keeping its grades, and back', function (): void {
    $persona = Persona::factory()->create();
    ActasFinales::historial($persona, 'Inglés', [[1, '16.00', 'aprobado']]);
    $acta = Acta::query()->sole();
    DB::statement('ALTER TABLE cer_actas DISABLE TRIGGER cer_actas_bloqueo');
    DB::table('cer_actas')->where('id', $acta->id)->update(['componentes' => json_encode([
        ['id' => 1, 'codigo' => 'parcial', 'nombre' => 'Examen parcial', 'peso' => '40.00'],
        ['id' => 2, 'codigo' => 'participacion', 'nombre' => 'Participación', 'peso' => '20.00'],
        ['id' => 3, 'codigo' => 'oral', 'nombre' => 'Expresión oral', 'grupo' => 'habilidades'],
    ])]);
    DB::statement('ALTER TABLE cer_actas ENABLE TRIGGER cer_actas_bloqueo');

    migracionDeColumnasDelActa()->up();
    $columnas = $acta->refresh()->componentesDeEvaluacion();

    expect(array_map(fn (ComponenteDeEvaluacion $c): array => [$c->codigo, $c->tipo, $c->fuentes, $c->ciclo], $columnas))->toEqual([
        ['parcial', TipoDeComponente::Ingresada, [], null],
        ['participacion', TipoDeComponente::Ingresada, [], null],
        ['oral', TipoDeComponente::Ingresada, [], null],
        ['promedio_habilidades', TipoDeComponente::Calculada, [['codigo' => 'participacion', 'peso' => 1], ['codigo' => 'oral', 'peso' => 1]], null],
        ['promedio_examenes', TipoDeComponente::Calculada, [['codigo' => 'parcial', 'peso' => 1]], null],
        ['nota_final', TipoDeComponente::Calculada, [['codigo' => 'promedio_habilidades', 'peso' => 1], ['codigo' => 'promedio_examenes', 'peso' => 1]], 1],
    ])
        ->and($columnas[5]->redondeo)->toBe(RedondeoDeNota::Entero)
        ->and($acta->notas()->sole()->promedio)->toBe('16.00');

    migracionDeColumnasDelActa()->down();

    expect($acta->refresh()->componentes)->toEqual([
        ['id' => 1, 'codigo' => 'parcial', 'nombre' => 'Examen parcial', 'grupo' => 'examenes'],
        ['id' => 2, 'codigo' => 'participacion', 'nombre' => 'Participación', 'grupo' => 'habilidades'],
        ['id' => 3, 'codigo' => 'oral', 'nombre' => 'Expresión oral', 'grupo' => 'habilidades'],
    ]);
});
