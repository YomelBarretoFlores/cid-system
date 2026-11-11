<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Database\Factories;

use App\Modules\Docencia\Enums\EstadoDocente;
use App\Modules\Docencia\Models\CondicionLaboral;
use App\Modules\Docencia\Models\Docente;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Docente>
 */
final class DocenteFactory extends Factory
{
    #[\Override]
    protected $model = Docente::class;

    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'correo_institucional' => fake()->unique()->bothify('docente.????####').'@unasam.test',
            'ruc' => fake()->unique()->numerify('10#########'),
            'banco' => 'Banco de la Nación',
            'cuenta_bancaria' => fake()->numerify('04-###-######'),
            'condicion_laboral_id' => fn (): int => CondicionLaboral::idDe('contratado'),
            'estado' => EstadoDocente::Activo,
        ];
    }

    public function configure(): static
    {
        // The code is a generated column, so the created model reads it back.
        return $this->afterCreating(fn (Docente $docente): Docente => $docente->refresh());
    }

    public function enEstado(EstadoDocente $estado): static
    {
        return $this->state(['estado' => $estado]);
    }
}
