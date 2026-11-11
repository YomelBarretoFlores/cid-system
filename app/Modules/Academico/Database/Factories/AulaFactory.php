<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Models\Aula;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Aula>
 */
final class AulaFactory extends Factory
{
    #[\Override]
    protected $model = Aula::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('A-###'),
            'nombre' => fake()->words(2, true),
            'capacidad' => 30,
            'ubicacion' => null,
            'activo' => true,
        ];
    }
}
