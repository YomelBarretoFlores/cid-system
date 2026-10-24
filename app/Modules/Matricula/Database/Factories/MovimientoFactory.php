<?php

declare(strict_types=1);

namespace App\Modules\Matricula\Database\Factories;

use App\Modules\Academico\Models\Grupo;
use App\Modules\Matricula\Enums\TipoMovimiento;
use App\Modules\Matricula\Models\Matricula;
use App\Modules\Matricula\Models\Movimiento;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Movimiento>
 */
final class MovimientoFactory extends Factory
{
    protected $model = Movimiento::class;

    public function definition(): array
    {
        return [
            'matricula_id' => Matricula::factory(),
            'tipo' => TipoMovimiento::Retiro,
            'grupo_origen_id' => fn (array $attributes): int => Matricula::query()->whereKey($attributes['matricula_id'])->firstOrFail()->grupo_id,
            'grupo_destino_id' => null,
            'motivo' => 'Solicitud del estudiante por motivos laborales.',
            'autorizado_por' => User::factory(),
            'efectuado_en' => now(),
        ];
    }

    public function traslado(): static
    {
        return $this->state([
            'tipo' => TipoMovimiento::Traslado,
            'grupo_destino_id' => Grupo::factory(),
            'motivo' => 'Cruce de horarios con su jornada laboral.',
        ]);
    }
}
