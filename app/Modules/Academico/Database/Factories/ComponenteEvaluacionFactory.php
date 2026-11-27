<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Models\ComponenteEvaluacion;
use App\Modules\Academico\Models\EsquemaEvaluacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComponenteEvaluacion>
 */
final class ComponenteEvaluacionFactory extends Factory
{
    #[\Override]
    protected $model = ComponenteEvaluacion::class;

    public function definition(): array
    {
        return [
            'esquema_id' => EsquemaEvaluacion::factory(),
            'codigo' => fake()->unique()->lexify('comp-?????'),
            'nombre' => fake()->words(2, true),
            'tipo' => 'ingresada',
            'orden' => 1,
        ];
    }

    /**
     * @param  array<string, int>|list<string>  $fuentes  codes of earlier columns, weight 1 each, or code => weight
     */
    public function calculada(array $fuentes, string $calculo = 'promedio', string $redondeo = 'ninguno', ?int $ciclo = null): self
    {
        $lista = [];

        foreach ($fuentes as $clave => $valor) {
            $lista[] = is_int($clave) ? ['codigo' => (string) $valor, 'peso' => 1] : ['codigo' => $clave, 'peso' => (int) $valor];
        }

        return $this->state(['tipo' => 'calculada', 'calculo' => $calculo, 'redondeo' => $redondeo, 'fuentes' => $lista, 'ciclo' => $ciclo]);
    }

    /**
     * The CILN system on top of entered columns: average of the skills, average of the exams, and the cycle grade as their average rounded to an integer.
     *
     * @param  list<string>  $habilidades
     * @param  list<string>  $examenes
     */
    public static function promediosCiln(EsquemaEvaluacion $esquema, array $habilidades, array $examenes, int $desdeOrden): void
    {
        self::new()->for($esquema, 'esquema')->calculada($habilidades)->create(['codigo' => 'promedio_habilidades', 'nombre' => 'Promedio de habilidades', 'orden' => $desdeOrden]);
        self::new()->for($esquema, 'esquema')->calculada($examenes)->create(['codigo' => 'promedio_examenes', 'nombre' => 'Promedio de exámenes', 'orden' => $desdeOrden + 1]);
        self::new()->for($esquema, 'esquema')->calculada(['promedio_habilidades', 'promedio_examenes'], redondeo: 'entero', ciclo: 1)->create(['codigo' => 'nota_final', 'nombre' => 'Nota final', 'orden' => $desdeOrden + 2]);
    }
}
