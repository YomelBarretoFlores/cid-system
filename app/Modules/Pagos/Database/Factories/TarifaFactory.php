<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Database\Factories;

use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\Tarifa;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tarifa>
 */
final class TarifaFactory extends Factory
{
    #[\Override]
    protected $model = Tarifa::class;

    public function definition(): array
    {
        return [
            'concepto_id' => Concepto::factory(),
            'tipo_vinculo' => 'publico_general',
            'monto' => '150.00',
            'vigente_desde' => '2027-01-01',
            'vigente_hasta' => null,
        ];
    }
}
