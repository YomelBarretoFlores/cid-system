<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Database\Factories;

use App\Modules\Academico\Models\Ciclo;
use App\Modules\Academico\Models\Idioma;
use App\Modules\Matricula\Enums\EstadoSolicitudClasificacion;
use App\Modules\Matricula\Models\SolicitudClasificacion;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SolicitudClasificacion>
 */
final class SolicitudClasificacionFactory extends Factory
{
    #[\Override]
    protected $model = SolicitudClasificacion::class;

    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'idioma_id' => Idioma::factory(),
            'estado' => EstadoSolicitudClasificacion::PendienteEvaluacion,
            'vence_en' => now()->addDays(30)->toDateString(),
        ];
    }

    public function evaluada(): static
    {
        return $this->state([
            'estado' => EstadoSolicitudClasificacion::Evaluado,
            'fecha_examen' => now()->toDateString(),
            'puntaje' => '72.50',
            'puntaje_maximo' => 100,
            'ciclo_asignado_id' => fn (array $attributes): int => Ciclo::factory()->create(['idioma_id' => $attributes['idioma_id']])->id,
            'evaluado_por' => User::factory(),
            'evaluado_en' => now(),
        ]);
    }
}
