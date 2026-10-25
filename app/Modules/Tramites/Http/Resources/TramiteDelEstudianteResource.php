<?php

declare(strict_types=1);

namespace App\Modules\Tramites\Http\Resources;

use App\Modules\Tramites\Models\Tramite;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Tramite $resource
 */
final class TramiteDelEstudianteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $tramite = $this->resource;

        return [
            'codigo' => $tramite->codigo,
            'tipo' => $tramite->tipo_nombre,
            'estado' => $tramite->estado->value,
            'estado_etiqueta' => $tramite->estado->etiqueta(),
            'avance' => $tramite->avance(),
            'etapa' => $tramite->nombreDeEtapa(),
            'etapas' => array_map(fn (array $etapa): array => ['nombre' => $etapa['nombre'], 'avance' => $etapa['avance']], $tramite->etapas),
            'observacion' => $tramite->observacion,
            'documento_emitido' => $tramite->documento_id !== null,
            'solicitado_en' => $tramite->created_at->setTimezone('America/Lima')->toIso8601String(),
            'finalizado_en' => $tramite->finalizado_en?->setTimezone('America/Lima')->toIso8601String(),
        ];
    }
}
