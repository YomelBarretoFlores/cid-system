<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Models\EsquemaEvaluacion;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EsquemaEvaluacion>
 */
final class EsquemaEvaluacionFactory extends Factory
{
    #[\Override]
    protected $model = EsquemaEvaluacion::class;

    public function definition(): array
    {
        return [
            'nombre' => 'Esquema general',
            'idioma_id' => null,
            'nivel' => null,
            'nota_maxima' => '20.00',
            'nota_minima_aprobatoria' => '11.00',
            'vigente_desde' => '2027-01-01',
            'vigente_hasta' => null,
        ];
    }
}
