<?php

declare(strict_types=1);

namespace App\Modules\Personas\Database\Factories;

use App\Modules\Personas\Enums\TipoDocumento;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Persona>
 */
final class PersonaFactory extends Factory
{
    #[\Override]
    protected $model = Persona::class;

    public function definition(): array
    {
        $faker = fake('es_PE');

        return [
            'user_id' => null,
            'tipo_documento' => TipoDocumento::Dni,
            'numero_documento' => $faker->unique()->numerify('########'),
            'apellido_paterno' => $faker->lastName(),
            'apellido_materno' => $faker->lastName(),
            'nombres' => $faker->firstName(),
            'fecha_nacimiento' => $faker->dateTimeBetween('-50 years', '-16 years')->format('Y-m-d'),
            'correo' => $faker->unique()->safeEmail(),
            'celular' => $faker->numerify('9########'),
            'tipo_vinculo' => 'publico_general',
        ];
    }

    public function estudianteUnasam(): static
    {
        return $this->state(['tipo_vinculo' => 'estudiante_unasam']);
    }

    public function extranjera(): static
    {
        return $this->state(fn (): array => [
            'tipo_documento' => TipoDocumento::Ce,
            'numero_documento' => fake()->unique()->bothify('#########'),
            'apellido_materno' => null,
        ]);
    }
}
