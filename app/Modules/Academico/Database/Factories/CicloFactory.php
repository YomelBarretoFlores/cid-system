<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Enums\NivelCurricular;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Idioma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ciclo>
 */
final class CicloFactory extends Factory
{
    #[\Override]
    protected $model = Ciclo::class;

    public function definition(): array
    {
        return [
            'idioma_id' => Idioma::factory(),
            'numero' => fake()->unique()->numberBetween(1, 12),
            'nivel' => NivelCurricular::Basico,
            'nombre' => 'Básico I',
            'horas_lectivas' => 60,
            'activo' => true,
        ];
    }
}
