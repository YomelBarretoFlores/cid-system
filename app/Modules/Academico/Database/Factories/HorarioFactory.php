<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Horario;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Horario>
 */
final class HorarioFactory extends Factory
{
    protected $model = Horario::class;

    public function definition(): array
    {
        return [
            'grupo_id' => Grupo::factory(),
            'dia_semana' => fake()->numberBetween(1, 6),
            'hora_inicio' => '08:00',
            'hora_fin' => '10:00',
        ];
    }
}
