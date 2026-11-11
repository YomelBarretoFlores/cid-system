<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Models\Idioma;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Idioma>
 */
final class IdiomaFactory extends Factory
{
    #[\Override]
    protected $model = Idioma::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('???##'),
            'nombre' => fake()->unique()->words(2, true),
            'es_lengua_nativa' => false,
            'activo' => true,
        ];
    }
}
