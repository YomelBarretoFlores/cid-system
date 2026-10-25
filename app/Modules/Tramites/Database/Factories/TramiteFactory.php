<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Database\Factories;

use App\Modules\Personas\Models\Persona;
use App\Modules\Tramites\Enums\EstadoTramite;
use App\Modules\Tramites\Models\Etapa;
use App\Modules\Tramites\Models\TipoTramite;
use App\Modules\Tramites\Models\Tramite;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Tramite>
 */
final class TramiteFactory extends Factory
{
    protected $model = Tramite::class;

    public function definition(): array
    {
        return [
            'persona_id' => Persona::factory(),
            'tipo_tramite_id' => TipoTramite::factory()->sinTasa(),
            'tipo_nombre' => 'Trámite de prueba',
            'accion_final' => 'ninguna',
            'tipo_concepto' => null,
            'etapas' => [],
            'etapa_actual' => 0,
            'estado' => EstadoTramite::EnProceso,
            'datos' => [],
        ];
    }

    // Copies the stages of its type, as a real request does.
    public function configure(): static
    {
        return $this->afterMaking(function (Tramite $tramite): void {
            if ($tramite->etapas !== []) {
                return;
            }

            $tipo = TipoTramite::query()->with('etapas')->findOrFail($tramite->tipo_tramite_id);
            $tramite->forceFill([
                'tipo_nombre' => $tipo->nombre,
                'accion_final' => $tipo->accion_final,
                'tipo_concepto' => $tipo->tipo_concepto,
                'etapas' => $tipo->etapas->map(fn (Etapa $etapa): array => ['nombre' => $etapa->nombre, 'avance' => $etapa->avance, 'condicion' => $etapa->condicion->value])->all(),
            ]);
        });
    }
}
