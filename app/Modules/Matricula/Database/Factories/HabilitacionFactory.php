<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Database\Factories;

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Matricula\Enums\OrigenHabilitacion;
use App\Modules\Matricula\Models\Habilitacion;
use App\Modules\Personas\Models\Persona;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Habilitacion>
 */
final class HabilitacionFactory extends Factory
{
    protected $model = Habilitacion::class;

    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'ciclo_id' => Ciclo::factory(),
            'idioma_id' => fn (array $attributes): int => Ciclo::query()->whereKey($attributes['ciclo_id'])->firstOrFail()->idioma_id,
            'origen' => OrigenHabilitacion::Clasificacion,
        ];
    }
}
