<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Database\Factories;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Matricula\Enums\CondicionMatricula;
use App\Modules\Matricula\Enums\EstadoMatricula;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Matricula>
 */
final class MatriculaFactory extends Factory
{
    protected $model = Matricula::class;

    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'grupo_id' => Grupo::factory(),
            'condicion' => CondicionMatricula::Regular,
            'estado' => EstadoMatricula::Activa,
            'matriculado_en' => now(),
        ];
    }

    public function retirada(): static
    {
        return $this->state(['estado' => EstadoMatricula::Retirada, 'retirado_en' => now()]);
    }
}
