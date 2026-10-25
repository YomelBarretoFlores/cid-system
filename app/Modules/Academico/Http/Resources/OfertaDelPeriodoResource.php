<?php

declare(strict_types=1);

namespace App\Modules\Academico\Http\Resources;

use App\Modules\Academico\Data\GrupoOfertado;
use App\Modules\Academico\Data\OfertaDelPeriodo;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property OfertaDelPeriodo $resource
 */
final class OfertaDelPeriodoResource extends JsonResource
{
    /**
     * @return array{periodo: string, matricula_abierta: bool, matricula_inicio: string, matricula_fin: string, clases_inicio: string, clases_fin: string, grupos: list<array{idioma: string, ciclo: int, nivel: string, modalidad: string, acelerado: bool, horario: list<string>, vacantes: int, abierto: bool}>}
     */
    public function toArray(Request $request): array
    {
        $oferta = $this->resource;

        return [
            'periodo' => $oferta->periodo,
            'matricula_abierta' => $oferta->matriculaAbierta,
            'matricula_inicio' => $oferta->matriculaInicio->toDateString(),
            'matricula_fin' => $oferta->matriculaFin->toDateString(),
            'clases_inicio' => $oferta->clasesInicio->toDateString(),
            'clases_fin' => $oferta->clasesFin->toDateString(),
            'grupos' => array_map(fn (GrupoOfertado $grupo): array => [
                'idioma' => $grupo->idioma,
                'ciclo' => $grupo->cicloNumero,
                'nivel' => $grupo->nivel,
                'modalidad' => $grupo->modalidad,
                'acelerado' => $grupo->esAcelerado,
                'horario' => $grupo->horario,
                'vacantes' => $grupo->vacantes,
                'abierto' => $grupo->abierto,
            ], $oferta->grupos),
        ];
    }
}
