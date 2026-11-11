<?php

declare(strict_types=1);

namespace App\Modules\Academico\Database\Factories;

use App\Modules\Academico\Enums\EstadoPeriodo;
use App\Modules\Academico\Models\Periodo;
use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\Factory;
use LogicException;

/**
 * @extends Factory<Periodo>
 */
final class PeriodoFactory extends Factory
{
    #[\Override]
    protected $model = Periodo::class;

    public function definition(): array
    {
        return [
            'anio' => 2027,
            'numero' => fn (array $atributos): int => $this->numeroLibre((int) $atributos['anio']),
            'nombre' => 'Periodo de prueba',
            'fecha_inicio' => '2027-03-01',
            'fecha_fin' => '2027-03-31',
            'matricula_inicio' => '2027-02-15',
            'matricula_fin' => '2027-03-05',
            'estado' => EstadoPeriodo::Planificado,
        ];
    }

    // A number not taken in that year nor by another period of the same test (count() builds them all before saving), so explicit numbers never collide.
    private function numeroLibre(int $anio): int
    {
        $libres = array_values(array_diff(range(1, Periodo::NUMERO_MAXIMO), Periodo::query()->where('anio', $anio)->pluck('numero')->all()));

        if ($libres === []) {
            throw new LogicException("No quedan números de periodo libres en {$anio}.");
        }

        return fake()->unique()->randomElement($libres);
    }

    public function conMatriculaAbierta(): static
    {
        $hoy = CarbonImmutable::today('America/Lima');

        return $this->state([
            'anio' => $hoy->year,
            'estado' => EstadoPeriodo::MatriculaAbierta,
            'matricula_inicio' => $hoy->subDays(3)->toDateString(),
            'matricula_fin' => $hoy->addDays(3)->toDateString(),
            'fecha_inicio' => $hoy->addDays(4)->toDateString(),
            'fecha_fin' => $hoy->addDays(60)->toDateString(),
        ]);
    }
}
