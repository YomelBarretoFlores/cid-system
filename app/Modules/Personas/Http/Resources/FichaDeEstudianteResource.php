<?php

declare(strict_types=1);

namespace App\Modules\Personas\Http\Resources;

use App\Modules\Personas\Data\FichaDeEstudiante;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property FichaDeEstudiante $resource
 */
final class FichaDeEstudianteResource extends JsonResource
{
    /**
     * @return array{nombres: string, nombre_completo: string, documento: string, correo: ?string, celular: ?string}
     */
    public function toArray(Request $request): array
    {
        return [
            'nombres' => $this->resource->nombres,
            'nombre_completo' => $this->resource->nombreCompleto,
            'documento' => $this->resource->documento,
            'correo' => $this->resource->correo,
            'celular' => $this->resource->celular,
        ];
    }
}
