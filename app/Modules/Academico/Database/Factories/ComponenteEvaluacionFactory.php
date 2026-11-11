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
            'peso' => '50.00',
            'orden' => 1,
        ];
    }
}
