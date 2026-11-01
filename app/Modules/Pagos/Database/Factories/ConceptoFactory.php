<?php

declare(strict_types=1);

namespace App\Modules\Pagos\Database\Factories;

use App\Modules\Pagos\Enums\TipoConcepto;
use App\Modules\Pagos\Models\Concepto;
use App\Modules\Pagos\Models\FuenteDeConcepto;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Concepto>
 */
final class ConceptoFactory extends Factory
{
    protected $model = Concepto::class;

    public function definition(): array
    {
        return [
            'codigo' => fake()->unique()->bothify('TUPA-??####'),
            'nombre' => 'Derecho de matrícula',
            'tipo' => TipoConcepto::Matricula,
            'fuente_id' => fn (): int => FuenteDeConcepto::idDe('tupa'),
            'activo' => true,
        ];
    }
}
