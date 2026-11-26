<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Resources;

use App\Modules\Certificacion\Data\NotaDelHistorial;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property array{idioma: string, notas: list<NotaDelHistorial>} $resource
 */
final class NotasPorIdiomaResource extends JsonResource
{
    /**
     * @return array{idioma: string, ciclos: list<array{ciclo: int, nombre: string, nivel: string, periodo: ?string, horas_lectivas: int, promedio: ?string, estado_final: string, estado_final_etiqueta: string}>}
     */
    public function toArray(Request $request): array
    {
        return [
            'idioma' => $this->resource['idioma'],
            'ciclos' => array_map(fn (NotaDelHistorial $nota): array => [
                'ciclo' => $nota->cicloNumero,
                'nombre' => $nota->cicloNombre,
                'nivel' => $nota->nivel->etiqueta(),
                'periodo' => $nota->periodo,
                'horas_lectivas' => $nota->horasLectivas,
                'promedio' => $nota->promedio,
                'estado_final' => $nota->estadoFinal->value,
                'estado_final_etiqueta' => $nota->estadoFinal->etiqueta(),
            ], $this->resource['notas']),
        ];
    }
}
