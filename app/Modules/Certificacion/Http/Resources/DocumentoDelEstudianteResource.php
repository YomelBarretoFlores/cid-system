<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Resources;

use App\Modules\Certificacion\Models\Documento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property Documento $resource
 */
final class DocumentoDelEstudianteResource extends JsonResource
{
    /**
     * @return array{codigo: string, tipo: string, tipo_etiqueta: string, idioma: string, nivel: ?string, emitido_el: string, vigente: bool, anulado_el: ?string, verificacion: string}
     */
    public function toArray(Request $request): array
    {
        $documento = $this->resource;

        return [
            'codigo' => $documento->codigo,
            'tipo' => $documento->tipo->value,
            'tipo_etiqueta' => $documento->tipo->etiqueta(),
            'idioma' => $documento->idioma,
            'nivel' => $documento->nivel?->etiqueta(),
            'emitido_el' => $documento->emitido_en->setTimezone('America/Lima')->toDateString(),
            'vigente' => $documento->estaVigente(),
            'anulado_el' => $documento->anulado_en?->setTimezone('America/Lima')->toDateString(),
            'verificacion' => route('verificacion.documento', $documento->codigo),
        ];
    }
}
