<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Database\Factories;

use App\Modules\Pagos\Enums\EstadoPago;
use App\Modules\Pagos\Enums\MedioPago;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Pago;
use App\Modules\Personas\Models\Persona;
use App\Shared\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Pago>
 */
final class PagoFactory extends Factory
{
    protected $model = Pago::class;

    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'concepto_id' => Concepto::factory(),
            'tarifa_id' => null,
            'monto' => '150.00',
            'medio_pago' => MedioPago::BancoNacion,
            'numero_operacion' => fake()->unique()->numerify('########'),
            'fecha_pago' => now()->toDateString(),
            'recibo_path' => 'recibos/'.fake()->uuid().'.pdf',
            'estado' => EstadoPago::Pendiente,
        ];
    }

    public function pendiente(): static
    {
        return $this->state(['estado' => EstadoPago::Pendiente]);
    }

    public function validado(): static
    {
        return $this->state([
            'estado' => EstadoPago::Validado,
            'validado_por' => User::factory(),
            'validado_en' => now(),
        ]);
    }

    public function rechazado(): static
    {
        return $this->state([
            'estado' => EstadoPago::Rechazado,
            'validado_por' => User::factory(),
            'validado_en' => now(),
            'motivo_rechazo' => 'El número de operación no coincide con el recibo.',
        ]);
    }

    public function anulado(): static
    {
        return $this->validado()->state([
            'estado' => EstadoPago::Anulado,
            'anulado_por' => User::factory(),
            'anulado_en' => now(),
            'motivo_anulacion' => 'Pago registrado dos veces por error de caja.',
        ]);
    }
}
