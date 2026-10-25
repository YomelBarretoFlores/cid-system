<?php

declare(strict_types=1);

namespace App\Modules\Docencia\Http\Resources;

use App\Modules\Academico\Data\HorarioDeGrupo;
use App\Modules\Academico\Data\SesionDeClase;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @property HorarioDeGrupo $resource
 */
final class GrupoDelDocenteResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $grupo = $this->resource;

        return [
            'codigo' => $grupo->codigo,
            'estado' => $grupo->estado->value,
            'idioma' => $grupo->idioma,
            'nivel' => $grupo->nivel->etiqueta(),
            'ciclo' => $grupo->cicloNumero,
            'periodo' => $grupo->periodo,
            'periodo_inicio' => $grupo->periodoInicio->toDateString(),
            'periodo_fin' => $grupo->periodoFin->toDateString(),
            'sesiones' => array_map(fn (SesionDeClase $sesion): array => ['dia' => $sesion->diaSemana, 'hora_inicio' => substr($sesion->horaInicio, 0, 5), 'hora_fin' => substr($sesion->horaFin, 0, 5)], $grupo->sesiones),
            'horario' => SesionDeClase::compactar($grupo->sesiones),
        ];
    }
}
