<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Periodo;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Periodo>
 */
final class PeriodoFactory extends Factory
{
    protected $model = Periodo::class;

    public function definition(): array
    {
        return [
            'anio' => 2027,
            'numero' => fake()->unique()->numberBetween(1, 12),
            'nombre' => 'Periodo de prueba',
            'fecha_inicio' => '2027-03-01',
            'fecha_fin' => '2027-03-31',
            'matricula_inicio' => '2027-02-15',
            'matricula_fin' => '2027-03-05',
            'estado' => EstadoPeriodo::Planificado,
        ];
    }
}
