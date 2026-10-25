<?php

declare(strict_types=1);

namespace App\Modules\Certificacion\Http\Resources;

use App\Modules\Certificacion\Data\VerificacionDeDocumento;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property VerificacionDeDocumento $resource
 */
final class VerificacionDeDocumentoResource extends JsonResource
{
    /**
     * @return array{codigo: string, tipo: string, vigente: bool, emitido_el: string, anulado_el: ?string, estudiante: string, documento: string, idioma: string, nivel: ?string, horas_lectivas: ?int, nota_final: ?string, resumen: string}
     */
    public function toArray(Request $request): array
    {
        $verificacion = $this->resource;

        return [
            'codigo' => $verificacion->codigo,
            'tipo' => $verificacion->tipo,
            'vigente' => $verificacion->vigente,
            'emitido_el' => $verificacion->fechaDeEmision,
            'anulado_el' => $verificacion->fechaDeAnulacion,
            'estudiante' => $verificacion->estudiante,
            'documento' => $verificacion->documentoOculto,
            'idioma' => $verificacion->idioma,
            'nivel' => $verificacion->nivel,
            'horas_lectivas' => $verificacion->horasLectivas,
            'nota_final' => $verificacion->notaFinal,
            'resumen' => $verificacion->resumen,
        ];
    }
}
