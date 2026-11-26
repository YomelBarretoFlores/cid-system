<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Enums\RegimenDePrograma;
use App\Modules\Academico\Models\Programa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Programa>
 */
final class ProgramaFactory extends Factory
{
    #[\Override]
    protected $model = Programa::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('PRG-###'),
            'nombre' => fake()->unique()->words(3, true),
            'regimen' => RegimenDePrograma::Ambos,
            'activo' => true,
        ];
    }
}
