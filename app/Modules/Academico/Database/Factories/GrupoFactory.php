<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Enums\EstadoGrupo;
use App\Modules\Academico\Enums\Modalidad;
use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Grupo;
use App\Modules\Academico\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Grupo>
 */
final class GrupoFactory extends Factory
{
    #[\Override]
    protected $model = Grupo::class;

    public function definition(): array
    {
        return [
            'periodo_id' => Periodo::factory(),
            'ciclo_id' => Ciclo::factory(),
            'aula_id' => null,
            'codigo' => fake()->unique()->bothify('G-####'),
            'modalidad' => Modalidad::Presencial,
            'es_acelerado' => false,
            'enlace_virtual' => null,
            'cupo_maximo' => 25,
            'cupo_minimo' => 10,
            'estado' => EstadoGrupo::Abierto,
        ];
    }
}
